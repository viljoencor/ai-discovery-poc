#!/bin/sh
set -eu

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Local demonstration only.
# Never use this key-generation workflow in production.
php artisan key:generate --force --no-interaction >/dev/null

attempt=0

until php -r 'try {$p=new PDO("mysql:host=".getenv("DB_HOST").";port=3306;dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0);}catch(Throwable $e){exit(1);}'; do
    attempt=$((attempt + 1))

    if [ "$attempt" -ge 30 ]; then
        echo 'MariaDB not reachable' >&2
        exit 1
    fi

    sleep 2
done

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction

# Ensure HTTP and CLI processes use the same configuration.
php artisan config:cache

exec php artisan serve --host=0.0.0.0 --port=8000