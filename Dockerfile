###
# Production build for gRAT Server (single image using serversideup/php fpm+nginx).
###

# 1) Build frontend assets
FROM node:20-alpine AS assets
WORKDIR /app

ARG VITE_API_BASE_URL=/api
ARG VITE_SERVER_URL
ARG VITE_BASE=/

ENV VITE_API_BASE_URL=${VITE_API_BASE_URL}
ENV VITE_SERVER_URL=${VITE_SERVER_URL}
ENV VITE_BASE=${VITE_BASE}

COPY package*.json ./
RUN npm ci --legacy-peer-deps --ignore-scripts
COPY . .
RUN npm run build

# 2) Install PHP dependencies and pre-cache app
FROM serversideup/php:8.4-fpm AS php-builder
WORKDIR /var/www/html

# Base PHP extensions + build tooling
USER root
RUN apt-get update \
  && apt-get install -y git unzip default-mysql-client postgresql-client \
  && rm -rf /var/lib/apt/lists/*
RUN install-php-extensions intl bcmath

# Composer install (no dev) before copying the full tree to leverage cache
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-scripts

# Copy source + built assets, then finalize install and caches
COPY . .
COPY --from=assets /app/public/build public/build
RUN test -f public/build/manifest.json
RUN rm -f bootstrap/cache/*.php \
  && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

#
# Note: we do not cache config/routes/views at build time.
# This matches the master-branch behavior and avoids baking a missing APP_KEY
# into cached config when the image is built without a runtime .env file.

# 3) Runtime image with Nginx + PHP-FPM (serversideup)
FROM serversideup/php:8.4-fpm-nginx AS prod
WORKDIR /var/www/html

ARG APP_NAME=gRATify
ARG HASH_DRIVER=bcrypt
ARG ASSET_URL=/
ARG PUBLIC_PATH=/
ARG RESOURCE_ROUTE=/
ARG VITE_BASE=/
ARG VITE_API_BASE_URL=/api

USER root
RUN apt-get update \
  && apt-get install -y --no-install-recommends ca-certificates gnupg2 curl apt-transport-https unixodbc unixodbc-dev default-mysql-client postgresql-client \
  && . /etc/os-release \
  && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft.gpg \
  && if [ "${ID}" = "debian" ]; then \
      ms_version="${VERSION_ID}"; ms_codename="${VERSION_CODENAME}"; \
    if [ "${VERSION_ID}" = "13" ]; then ms_version="12"; ms_codename="bookworm"; fi; \
    if [ "${VERSION_ID}" = "12" ]; then ms_version="11"; ms_codename="bullseye"; fi; \
      echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/${ms_version}/prod ${ms_codename} main" > /etc/apt/sources.list.d/microsoft-prod.list; \
    elif [ "${ID}" = "ubuntu" ]; then \
      ms_version="${VERSION_ID}"; ms_codename="${VERSION_CODENAME}"; \
      if [ "${VERSION_ID}" = "24.04" ]; then ms_version="22.04"; ms_codename="jammy"; fi; \
      echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/ubuntu/${ms_version}/prod ${ms_codename} main" > /etc/apt/sources.list.d/microsoft-prod.list; \
    else \
      echo "Unsupported base image for SQL Server drivers: ${ID}"; exit 1; \
    fi \
  && apt-get update \
  && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
  && rm -rf /var/lib/apt/lists/*
RUN install-php-extensions intl bcmath pdo_mysql pdo_pgsql pdo_sqlite pdo_sqlsrv sqlsrv
RUN set -eux; \
  if command -v apt-get >/dev/null; then \
    apt-get update && apt-get install -y --no-install-recommends sqlite3 && rm -rf /var/lib/apt/lists/*; \
  elif command -v apk >/dev/null; then \
    apk add --no-cache sqlite sqlite-libs; \
  elif command -v dnf >/dev/null; then \
    dnf -y install sqlite && dnf clean all; \
  elif command -v yum >/dev/null; then \
    yum -y install sqlite && yum clean all; \
  else \
    echo "No supported package manager found (apt/apk/dnf/yum)"; exit 1; \
  fi

COPY --from=php-builder /var/www/html /var/www/html
COPY scripts/generate-env /usr/local/bin/generate-env
RUN chmod +x /usr/local/bin/generate-env
RUN mkdir -p storage/app/private/backups \
  && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
  && chown -R www-data:www-data storage bootstrap/cache
# Override default Nginx site with our production config
COPY docker/nginx/production.conf /etc/nginx/sites-available/default

ENV PORT=8080
ENV APP_NAME=${APP_NAME} \
    HASH_DRIVER=${HASH_DRIVER} \
    BCRYPT_ROUNDS=12 \
    ASSET_URL=${ASSET_URL} \
    PUBLIC_PATH=${PUBLIC_PATH} \
    RESOURCE_ROUTE=${RESOURCE_ROUTE} \
    VITE_BASE=${VITE_BASE} \
    VITE_API_BASE_URL=${VITE_API_BASE_URL} \
    PHP_FPM_USER=www-data \
    PHP_FPM_GROUP=www-data
# Enable and tune OPcache for production
ENV PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_OPCACHE_INTERNED_STRINGS_BUFFER=16 \
    PHP_OPCACHE_JIT=0 \
    PHP_OPCACHE_JIT_BUFFER_SIZE=0
EXPOSE 8080

###
# Optional dev target: keeps dev dependencies for local testing.
###
FROM serversideup/php:8.4-fpm-nginx AS dev
WORKDIR /var/www/html

USER root
RUN apt-get update \
  && apt-get install -y --no-install-recommends ca-certificates gnupg2 curl apt-transport-https unixodbc unixodbc-dev \
  && . /etc/os-release \
  && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft.gpg \
  && if [ "${ID}" = "debian" ]; then \
      ms_version="${VERSION_ID}"; ms_codename="${VERSION_CODENAME}"; \
    if [ "${VERSION_ID}" = "13" ]; then ms_version="12"; ms_codename="bookworm"; fi; \
    if [ "${VERSION_ID}" = "12" ]; then ms_version="11"; ms_codename="bullseye"; fi; \
      echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/${ms_version}/prod ${ms_codename} main" > /etc/apt/sources.list.d/microsoft-prod.list; \
    elif [ "${ID}" = "ubuntu" ]; then \
      ms_version="${VERSION_ID}"; ms_codename="${VERSION_CODENAME}"; \
      if [ "${VERSION_ID}" = "24.04" ]; then ms_version="22.04"; ms_codename="jammy"; fi; \
      echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/ubuntu/${ms_version}/prod ${ms_codename} main" > /etc/apt/sources.list.d/microsoft-prod.list; \
    else \
      echo "Unsupported base image for SQL Server drivers: ${ID}"; exit 1; \
    fi \
  && apt-get update \
  && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
  && rm -rf /var/lib/apt/lists/*
RUN install-php-extensions intl bcmath pdo_mysql pdo_pgsql pdo_sqlite pdo_sqlsrv sqlsrv

# Node/NPM for frontend tooling in dev target
RUN apt-get update \
  && apt-get install -y --no-install-recommends nodejs npm default-mysql-client postgresql-client \
  && rm -rf /var/lib/apt/lists/*

# Copy full source (no build artifacts required for HMR/tests)
COPY . .

# Install PHP dev dependencies and JS deps
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist \
    && npm ci --legacy-peer-deps --ignore-scripts

RUN mkdir -p storage/app/private/backups \
  && chown -R www-data:www-data storage bootstrap/cache

ENV PORT=8080
ENV PHP_OPCACHE_JIT=0 \
    PHP_OPCACHE_JIT_BUFFER_SIZE=0
EXPOSE 8080
