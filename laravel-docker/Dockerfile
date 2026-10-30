FROM php:8.3-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev libonig-dev libxml2-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring intl zip \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
# Generate the standard Laravel skeleton from its official Composer distribution.
RUN composer create-project laravel/laravel:^13.0 . --prefer-dist --no-interaction
COPY laravel-overlay/ /var/www/html/
COPY scripts/start-laravel.sh /usr/local/bin/start-laravel.sh
RUN chmod +x /usr/local/bin/start-laravel.sh
EXPOSE 8000
CMD ["/usr/local/bin/start-laravel.sh"]
