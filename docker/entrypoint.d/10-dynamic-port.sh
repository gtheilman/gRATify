#!/bin/sh
set -eu

DYNAMIC_PORT="${DYNAMIC_PORT:-0}"
PORT="${PORT:-8080}"

if [ "$DYNAMIC_PORT" != "1" ]; then
  echo "[dynamic-port] DYNAMIC_PORT!=1; leaving nginx default port (8080)."
  exit 0
fi

echo "[dynamic-port] DYNAMIC_PORT=1; setting nginx listen port to ${PORT}."

export NGINX_HTTP_PORT="${PORT}"
echo "[dynamic-port] Set NGINX_HTTP_PORT=${NGINX_HTTP_PORT}."
if [ -d /var/run/s6/container_environment ]; then
  echo -n "${NGINX_HTTP_PORT}" > /var/run/s6/container_environment/NGINX_HTTP_PORT
fi
if [ -d /run/s6/container_environment ]; then
  echo -n "${NGINX_HTTP_PORT}" > /run/s6/container_environment/NGINX_HTTP_PORT
fi

patch_file() {
  f="$1"
  [ -f "$f" ] || return 0

  if grep -Eq 'NGINX_HTTP_PORT' "$f"; then
    echo "[dynamic-port] Patching $f (template port)"
    sed -i "s/\\\${NGINX_HTTP_PORT}/${PORT}/g" "$f"
  fi

  if ! grep -Eq 'listen[[:space:]]+8080([^0-9]|;)' "$f"; then
    return 0
  fi

  echo "[dynamic-port] Patching $f"
  sed -i -E "s/(listen[[:space:]]+)8080([^0-9])/\\1${PORT}\\2/g" "$f"
}

for f in \
  /etc/nginx/site-opts.d/http.conf.template \
  /etc/nginx/site-opts.d/https.conf.template \
  /etc/nginx/templates/default.conf.template \
; do
  patch_file "$f"
done

for f in \
  /etc/nginx/sites-available/default \
  /etc/nginx/sites-enabled/default \
  /etc/nginx/conf.d/default.conf \
  /etc/nginx/http.d/default.conf \
; do
  patch_file "$f"
done

matches="$(grep -RIlE 'listen[[:space:]]+8080([^0-9]|;)' /etc/nginx 2>/dev/null || true)"
if [ -n "$matches" ]; then
  echo "$matches" | head -n 20 | while IFS= read -r f; do
    patch_file "$f"
  done
fi
