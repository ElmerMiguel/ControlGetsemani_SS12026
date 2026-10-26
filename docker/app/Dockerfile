# ==============================================================================
# Dockerfile Producción — Sistema de Finanzas e Inventario Iglesia Getsemaní
# Multi-stage build: Node.js (Vite) + Composer (PHP) + Runtime (PHP 8.2-FPM)
# Reglas: ARQUITECTURA_FASE3.md §6 | Prompt P21
# ==============================================================================

# ------------------------------------------------------------------------------
# Etapa 1: Compilación de Frontend con Vite / Tailwind / Alpine.js
# ------------------------------------------------------------------------------
FROM node:20-alpine AS assets_builder

WORKDIR /app

# Copiar archivos de configuración y fuentes requeridos
COPY package*.json ./
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources resources/
COPY public public/

# Configurar reintentos robustos ante latencia de red en registry npm
RUN npm config set fetch-retries 5 \
    && npm config set fetch-retry-mintimeout 20000 \
    && npm config set fetch-retry-maxtimeout 120000 \
    && (npm ci && npm run build || (test -f public/build/manifest.json && echo "==> Reutilizando assets compilados en public/build") || exit 1)

# ------------------------------------------------------------------------------
# Etapa 2: Dependencias de Composer (optimizado sin herramientas de desarrollo)
# ------------------------------------------------------------------------------
FROM composer:2 AS vendor_builder

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# ------------------------------------------------------------------------------
# Etapa 3: Imagen Final de Producción (PHP 8.2-FPM Alpine)
# ------------------------------------------------------------------------------
FROM php:8.2-fpm-alpine

LABEL maintainer="Elmer Wilson <clave13@seminario.edu>"
LABEL description="Getsemani Finanzas - Produccion FPM"

# Instalar librerías de sistema requeridas para extensiones PHP y AWS CLI
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng \
    libpng-dev \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    freetype \
    freetype-dev \
    libzip \
    libzip-dev \
    oniguruma \
    oniguruma-dev \
    libxml2 \
    libxml2-dev \
    mariadb-client \
    aws-cli \
    tzdata \
    && cp /usr/share/zoneinfo/America/Guatemala /etc/localtime \
    && echo "America/Guatemala" > /etc/timezone \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
    && apk del --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        libxml2-dev \
    && rm -rf /var/cache/apk/*

# Configuraciones de PHP y OPcache para producción
COPY docker/php/production.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Directorio de trabajo
WORKDIR /var/www/html

# 1. Copiar código del proyecto
COPY . /var/www/html

# 2. Copiar dependencias de Composer optimizadas (sin paquetes dev)
COPY --from=vendor_builder /app/vendor /var/www/html/vendor

# 3. Copiar assets compilados de Vite (sin node_modules en la imagen final)
COPY --from=assets_builder /app/public/build /var/www/html/public/build

# 4. Asegurar estructura de directorios y permisos para www-data
RUN mkdir -p /var/www/html/storage/app/comprobantes \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 5. Configurar script de inicio (entrypoint)
COPY scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exponer puerto 9000 para FastCGI (comunicación interna con Nginx)
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["php-fpm"]
