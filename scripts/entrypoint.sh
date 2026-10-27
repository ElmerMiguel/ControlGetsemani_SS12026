#!/bin/sh
set -e

echo "==> [Entrypoint Producción] Inicializando contenedor..."

# 1. Crear directorios críticos de almacenamiento y caché si no existen
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/comprobantes \
         /var/www/html/storage/app/private \
         /var/www/html/bootstrap/cache

# 2. Permisos estrictos para www-data
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Carga segura de variables de entorno (D3-07 / Prompt P21 §4)
SSM_LOADED=false
if command -v aws >/dev/null 2>&1; then
    AWS_REGION="${AWS_DEFAULT_REGION:-${AWS_REGION:-us-east-1}}"
    if aws sts get-caller-identity --region "$AWS_REGION" >/dev/null 2>&1; then
        echo "==> [AWS SSM] Rol IAM detectado. Descargando parámetros desde /getsemani/*..."
        SSM_PARAMS=$(aws ssm get-parameters-by-path --path "/getsemani/" --with-decryption --recursive --region "$AWS_REGION" --query "Parameters[*].[Name,Value]" --output text 2>/dev/null || true)
        if [ -n "$SSM_PARAMS" ]; then
            echo "# Generado en runtime desde AWS SSM Parameter Store" > /var/www/html/.env
            echo "$SSM_PARAMS" | while read -r name value; do
                var_key=$(echo "$name" | sed 's|^/getsemani/||' | tr '[:lower:]' '[:upper:]' | tr '/' '_')
                echo "${var_key}=${value}" >> /var/www/html/.env
            done
            chmod 600 /var/www/html/.env
            SSM_LOADED=true
            echo "==> [AWS SSM] Parámetros cargados exitosamente en .env de runtime."
        fi
    fi
fi

# Fallback: Entorno local o VPS tradicional sin AWS SSM
if [ "$SSM_LOADED" = "false" ]; then
    if [ ! -f /var/www/html/.env ]; then
        if [ -f /var/www/html/.env.prod ]; then
            echo "==> [Config] Usando .env.prod local como .env de runtime."
            cp /var/www/html/.env.prod /var/www/html/.env
        elif [ -f /var/www/html/.env.prod.example ]; then
            echo "==> [Config] Usando .env.prod.example como plantilla de producción..."
            cp /var/www/html/.env.prod.example /var/www/html/.env
        elif [ -f /var/www/html/.env.example ]; then
            echo "==> [Config] Copiando .env.example..."
            cp /var/www/html/.env.example /var/www/html/.env
        fi
    fi
fi

# 4. Sincronizar credenciales y URLs de entorno en .env si existe
if [ -f /var/www/html/.env ]; then
    if [ -n "$APP_URL" ]; then
        sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" /var/www/html/.env 2>/dev/null || true
    fi
    if [ -n "$ASSET_URL" ]; then
        if grep -q "^ASSET_URL=" /var/www/html/.env; then
            sed -i "s|^ASSET_URL=.*|ASSET_URL=${ASSET_URL}|" /var/www/html/.env 2>/dev/null || true
        else
            echo "ASSET_URL=${ASSET_URL}" >> /var/www/html/.env
        fi
    fi
    if [ -n "$DB_HOST" ]; then
        sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|" /var/www/html/.env 2>/dev/null || true
    fi
    if [ -n "$DB_DATABASE" ]; then
        sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|" /var/www/html/.env 2>/dev/null || true
    fi
    if [ -n "$DB_USERNAME" ]; then
        sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|" /var/www/html/.env 2>/dev/null || true
    fi
    if [ -n "$DB_PASSWORD" ]; then
        sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" /var/www/html/.env 2>/dev/null || true
    fi
fi

# 5. Enlace simbólico de storage público
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link --force || true
fi

# 6. Manejo seguro y validado de APP_KEY (evita Unsupported cipher)
if [ -f /var/www/html/.env ]; then
    CURRENT_KEY=$(grep "^APP_KEY=" /var/www/html/.env | cut -d '=' -f2- | tr -d ' "\r\n' || true)
    if [ -z "$CURRENT_KEY" ] || echo "$CURRENT_KEY" | grep -q "CAMBIAR" || ! echo "$CURRENT_KEY" | grep -q "^base64:"; then
        echo "==> [Laravel] APP_KEY no válida o ausente. Generando clave criptográfica segura..."
        php artisan key:generate --force || true
    else
        echo "==> [Laravel] APP_KEY válida detectada (base64)."
    fi
fi

# 7. Optimización de cachés de Laravel en producción (D3-04)
echo "==> [Laravel] Optimizando cachés (config, route, view)..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 8. Migraciones automáticas en MariaDB para el servicio web
if [ "$1" = "apache2-foreground" ] || [ "$1" = "php-fpm" ]; then
    echo "==> [Laravel] Esperando a que MariaDB esté disponible para migraciones..."
    max_tries=15
    tries=0
    while ! php artisan db:monitor >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [ $tries -ge $max_tries ]; then
            echo "==> [Laravel] Aviso: Tiempo de espera agotado. Procediendo con migrate..."
            break
        fi
        sleep 2
    done
    echo "==> [Laravel] Ejecutando migraciones automáticas de base de datos..."
    php artisan migrate --force || true
fi

echo "==> [Entrypoint Producción] Listo. Arrancando servidor principal: $@"
exec "$@"
