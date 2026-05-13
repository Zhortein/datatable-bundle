FROM php:8.4-cli

# System deps + build deps for PHP extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git unzip zip curl \
    libzip-dev libxml2-dev libonig-dev libsqlite3-dev \
    nodejs npm \
    $PHPIZE_DEPS \
 && docker-php-ext-install -j"$(nproc)" \
    mbstring \
    dom \
    xml \
    zip \
    pdo_sqlite \
 && apt-get purge -y --auto-remove $PHPIZE_DEPS \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
