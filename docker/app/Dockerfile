# ==============================================================================
# Dockerfile Producción — Sistema de Finanzas e Inventario Iglesia Getsemaní
# Multi-stage build: Node.js (Vite) + PHP 8.2 Apache (Laravel All-In-One)
# ==============================================================================

# ------------------------------------------------------------------------------
# Etapa 1: Compilación de Frontend con Vite / Tailwind / Alpine.js
# ------------------------------------------------------------------------------
FROM node:20-alpine AS assets_builder

WORKDIR /app

# Copiar archivos de dependencias y configuración
COPY package*.json ./
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources resources/
COPY public public/

# Instalar dependencias npm y compilar assets
RUN npm config set fetch-retries 5 \
    && npm config set fetch-retry-mintimeout 20000 \
    && npm config set fetch-retry-maxtimeout 120000 \
    && npm ci \
    && npm run build

# ------------------------------------------------------------------------------
# Etapa 2: Imagen Final de Producción con PHP 8.2 y Apache
# ------------------------------------------------------------------------------
FROM php:8.2-apache

LABEL maintainer="Elmer Wilson <clave13@seminario.edu>"
LABEL description="Getsemani Finanzas - Produccion Apache + PHP 8.2"

# 1. Instalar librerías de sistema requeridas para extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    default-mysql-client \
    tzdata \
    && cp /usr/share/zoneinfo/America/Guatemala /etc/localtime \
    && echo "America/Guatemala" > /etc/timezone \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Habilitar mod_rewrite de Apache para rutas limpias de Laravel
RUN a2enmod rewrite headers

# 3. Configurar DocumentRoot de Apache apuntando a /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar ServerName y permitir sobrescritura en .htaccess
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i '/<Directory ${APACHE_DOCUMENT_ROOT}>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf 2>/dev/null || true \
    && echo '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# 4. Instalar Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5. Directorio de trabajo
WORKDIR /var/www/html

# 6. Copiar código del proyecto
COPY . /var/www/html

# 7. Copiar los assets compilados de Vite desde la Etapa 1
COPY --from=assets_builder /app/public/build /var/www/html/public/build

# 8. Instalar dependencias de Composer optimizadas para producción
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# 9. Asegurar estructura de directorios y permisos para www-data
RUN mkdir -p /var/www/html/storage/app/comprobantes \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Copiar script de entrypoint
COPY scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["apache2-foreground"]
