#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /run/nginx
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

if [ ! -L public/storage ]; then
    php artisan storage:link --force || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

envsubst '${PORT}' < /var/www/html/nginx.conf > /etc/nginx/conf.d/default.conf

php-fpm -F &
php_fpm_pid=$!

nginx -g 'daemon off;' &
nginx_pid=$!

cleanup() {
    kill -TERM "${php_fpm_pid}" "${nginx_pid}" 2>/dev/null || true
}

trap cleanup INT TERM

set +e
wait -n "${php_fpm_pid}" "${nginx_pid}"
status=$?
set -e

cleanup
wait "${php_fpm_pid}" "${nginx_pid}" 2>/dev/null || true

exit "${status}"
