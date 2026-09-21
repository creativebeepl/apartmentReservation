#!/bin/sh
set -e
cd /app

if [ ! -d node_modules/.bin ]; then
  npm install --no-audit --no-fund
fi

exec "$@"
