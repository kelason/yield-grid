# YieldGrid backend — local dev image (PHP 8.3 to match CI).
# One image, four commands: serve (api), reverb:start, queue:work, schedule:work.
FROM php:8.3-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    PUPPETEER_SKIP_CHROME_DOWNLOAD=false

WORKDIR /var/www/html

# System deps: composer needs (git/unzip), pgsql/gd/zip/intl/mbstring dev libs,
# postgres client (debugging), Node 22 (Browsershot/puppeteer) + Chrome runtime libs.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl unzip zip gnupg ca-certificates \
        libpq-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libzip-dev libicu-dev libonig-dev \
        postgresql-client \
        fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 \
        libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 \
        libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 \
        libpangocairo-1.0-0 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 \
        libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 \
        libxrender1 libxss1 libxtst6 lsb-release xdg-utils \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions: pgsql (PostGIS), gd (QR codes), pcntl+sockets (Reverb/queue).
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql intl zip bcmath exif mbstring pcntl sockets gd opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP + JS deps first (better layer caching; code changes don't bust this).
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --optimize-autoloader --no-scripts

COPY package.json package-lock.json ./
RUN npm ci && npx puppeteer browsers install chrome

# App code + entrypoint (bind mount overlays code at runtime; vendor/ and
# node_modules/ live in named volumes so Linux binaries survive a macOS host).
COPY . .
RUN php artisan package:discover --ansi
COPY docker/entrypoint.sh /usr/local/bin/yieldgrid-entrypoint
RUN chmod +x /usr/local/bin/yieldgrid-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# artisan serve only forwards $_ENV (never the full environment) to the PHP
# server subprocess, and only with --no-reload. Populate $_ENV so compose
# env overrides (DB_HOST, REDIS_HOST, ...) reach HTTP requests instead of
# silently falling back to .env file values.
RUN echo 'variables_order = "EGPCS"' > /usr/local/etc/php/conf.d/yieldgrid.ini

EXPOSE 8000

ENTRYPOINT ["yieldgrid-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
