#!/usr/bin/env bash
set -e

# Auto-generar APP_KEY si está vacío o es un placeholder (no requiere `make keys`)
if [ -z "${APP_KEY}" ] || [[ "${APP_KEY}" == *REPLACE* ]]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  echo "[entrypoint] APP_KEY generado automáticamente."
fi

echo "[entrypoint] esperando base de datos ${DB_HOST}:${DB_PORT}..."
until nc -z "${DB_HOST}" "${DB_PORT}"; do sleep 2; done
echo "[entrypoint] base de datos lista."

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache || true
php artisan config:clear || true

# Comando personalizado (p.ej. servidor Reverb): ejecutarlo sin migrar
if [ "$#" -gt 0 ]; then
  echo "[entrypoint] comando personalizado: $*"
  exec "$@"
fi

php artisan migrate --force || true
if [ -f database/seeders/DatabaseSeeder.php ] && [ "${RUN_SEED:-true}" = "true" ]; then
  php artisan db:seed --force || true
fi

echo "[entrypoint] servidor HTTP en :8000"
exec php artisan serve --host=0.0.0.0 --port=8000
