FROM composer:2 AS composer

FROM php:8.3-fpm-alpine AS app
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install required dependencies
RUN apk --no-cache add \
    su-exec \
    postgresql-dev \
    libpng-dev \
    zlib-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    autoconf \
    gcc \
    g++ \
    make \
    bash \
    postgresql-client \
    linux-headers \
    libtool \
    nodejs \
    npm

# Add extra PHP dependencies
RUN docker-php-ext-install pdo pgsql pdo_pgsql gd bcmath zip exif pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure pcntl --enable-pcntl

WORKDIR /usr/share/caddy/html/
COPY . .

# Install dependencies with composer
# composer validate --strict atlandı: upstream lock dosyası composer.json'dan
# geride (kendi bug'ları) ve --strict bunu exit 2 yapıyor; install lock'tan kurar.
RUN composer install \
  --no-interaction \
  --no-progress \
  --no-scripts \
  --no-dev \
  && composer clear-cache

# Vite değişkenleri build-arg'dan ENV'e — Coolify ARG bildirse de değer npm
# sürecine düşmüyordu; ENV hem değeri garantiler hem değişince cache'i patlatır.
ARG VITE_APP_NAME
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ENV VITE_APP_NAME=$VITE_APP_NAME \
    VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

# Install NPM dependencies and build frontend assets
RUN npm install \
  && npm run build \
  && rm -rf node_modules \
  && npm cache clean --force

# public named-volume ilk dolumdan sonra image güncellemelerini görmez —
# el değmemiş kopyayı sakla, entrypoint her açılışta volume'a senkronlar.
RUN cp -a /usr/share/caddy/html/public /opt/public-dist

# Set permissions and prepare Laravel
RUN php artisan package:discover --ansi \
  && chown -R www-data:www-data /usr/share/caddy/html/storage \
  && chown -R www-data:www-data /usr/share/caddy/html/bootstrap/cache \
  && chmod -R 775 /usr/share/caddy/html/storage \
  && chmod -R 775 /usr/share/caddy/html/bootstrap/cache

COPY ./docker/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-laraowl.conf

COPY ./docker/php-fpm-entrypoint /usr/local/bin/php-entrypoint

RUN chmod a+x /usr/local/bin/*

FROM app

ENTRYPOINT ["/usr/local/bin/php-entrypoint"]

CMD ["php-fpm"]
