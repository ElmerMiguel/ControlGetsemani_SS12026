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
        elif [ -f /var/www/html/.env.example ]; then
            echo "==> [Config] Advertencia: .env.prod no encontrado. Copiando .env.example..."
            cp /var/www/html/.env.example /var/www/html/.env
            php artisan key:generate --force || true
        fi
    fi
fi

# 4. Enlace simbólico de storage público
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link --force || true
fi

# 5. Sincronizar directorio public/ a volumen compartido si existe /shared/public
if [ -d "/shared/public" ]; then
    cp -ru /var/www/html/public/. /shared/public/ 2>/dev/null || true
fi

# 6. Optimización de cachés de Laravel en producción (D3-04)
# Solo si existe APP_KEY y la base de datos o configuración están listas
if [ -f /var/www/html/.env ] && grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    echo "==> [Laravel] Optimizando cachés (config, route, view)..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "==> [Entrypoint Producción] Listo. Ejecutando comando principal: $@"
exec "$@"
