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
# Prioridad: Si hay AWS CLI y rol IAM activo en EC2, consulta AWS SSM Parameter Store (/getsemani/*)
SSM_LOADED=false
if command -v aws >/dev/null 2>&1; then
    AWS_REGION="${AWS_DEFAULT_REGION:-${AWS_REGION:-us-east-1}}"
    # Verificar si responde el servicio STS o IMDS
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

# 4. Sincronizar credenciales de entorno en .env si existe
if [ -f /var/www/html/.env ]; then
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

# 6. Sincronizar directorio public/ a volumen compartido si existe /shared/public
if [ -d "/shared/public" ]; then
    cp -ru /var/www/html/public/. /shared/public/ 2>/dev/null || true
fi

# 7. Manejo seguro de APP_KEY
if [ -n "$APP_KEY" ]; then
    echo "==> [Laravel] APP_KEY detectada en variables de entorno."
    if [ -f /var/www/html/.env ]; then
        if grep -q "^APP_KEY=" /var/www/html/.env; then
            sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" /var/www/html/.env 2>/dev/null || true
        else
            echo "APP_KEY=${APP_KEY}" >> /var/www/html/.env
        fi
    fi
else
    if [ -f /var/www/html/.env ] && ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
        echo "==> [Laravel] Generando APP_KEY requerida..."
        php artisan key:generate --force || true
    fi
fi

# 8. Optimización de cachés de Laravel en producción (D3-04)
if [ -n "$APP_KEY" ] || grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    echo "==> [Laravel] Optimizando cachés (config, route, view)..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# 9. Migraciones automáticas en MariaDB para el servicio web
if [ "$1" = "php-fpm" ]; then
    echo "==> [Laravel] Esperando a que MariaDB esté disponible para migraciones..."
    max_tries=10
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
