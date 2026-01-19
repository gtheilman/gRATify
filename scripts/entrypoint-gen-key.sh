#!/bin/sh
set -e

# Generate APP_KEY if not provided. Useful for local docker-compose runs where .env isn't mounted.
if [ -z "${APP_KEY}" ]; then
  NEW_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
  export APP_KEY="${NEW_KEY}"
  echo "APP_KEY was empty; generated: ${NEW_KEY}"
fi

exec "$@"
