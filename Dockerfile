# FROM dunglas/frankenphp:php8.4-bookworm

# WORKDIR /app

# RUN apt-get update \
#     && apt-get install -y --no-install-recommends unzip \
#     && rm -rf /var/lib/apt/lists/*

# RUN install-php-extensions \
#     bcmath \
#     ctype \
#     curl \
#     dom \
#     fileinfo \
#     filter \
#     hash \
#     mbstring \
#     openssl \
#     pcre \
#     pdo \
#     pdo_mysql \
#     redis \
#     session \
#     tokenizer \
#     xml \
#     zip

# COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# COPY composer.json composer.lock ./

# RUN composer install \
#     --no-dev \
#     --no-interaction \
#     --prefer-dist \
#     --optimize-autoloader \
#     --no-scripts

# # Copier le reste de Laravel
# COPY . .

# # Installer Node
# COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
# COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
# RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# # Frontend
# RUN npm ci && npm run build

# # Permissions Laravel
# RUN mkdir -p \
#     storage/framework/cache \
#     storage/framework/sessions \
#     storage/framework/views \
#     storage/logs \
#     bootstrap/cache \
#     && chmod -R 775 storage bootstrap/cache

# # Cache Laravel
# RUN php artisan config:cache \
#     && php artisan event:cache \
#     && php artisan route:cache \
#     && php artisan view:cache

# # Port interne par défaut
# ENV SERVER_NAME=:8080

# EXPOSE 8080

# CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]



FROM dunglas/frankenphp:php8.4-bookworm

WORKDIR /app

# System dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN install-php-extensions \
    bcmath \
    ctype \
    curl \
    dom \
    fileinfo \
    filter \
    hash \
    mbstring \
    openssl \
    pcre \
    pdo \
    pdo_mysql \
    redis \
    session \
    tokenizer \
    xml \
    pcntl \
    zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Composer dependencies
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Application
COPY . .

# Node / npm
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# Frontend
RUN npm ci \
    && npm run build

# Laravel directories / permissions
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
    

# Railway provides PORT at runtime.
ENV SERVER_NAME=:8080

EXPOSE 8080

CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]