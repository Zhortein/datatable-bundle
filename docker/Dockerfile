# Node.js of the major version CI runs, on the Debian release of the PHP image
FROM node:22-trixie-slim AS node

FROM php:8.4-cli

# System deps + build deps for PHP extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git unzip zip curl \
    libzip-dev libxml2-dev libonig-dev libsqlite3-dev \
    $PHPIZE_DEPS \
 && docker-php-ext-install -j"$(nproc)" \
    mbstring \
    dom \
    xml \
    zip \
    pdo_sqlite \
 && apt-get purge -y --auto-remove $PHPIZE_DEPS \
 && rm -rf /var/lib/apt/lists/*

# Node.js and npm
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules/npm /usr/local/lib/node_modules/npm
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
