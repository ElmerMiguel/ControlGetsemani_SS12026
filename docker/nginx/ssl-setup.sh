#!/bin/sh
set -e

DOMAIN="${DOMAIN:-localhost}"

echo "==> [Nginx Setup] Configurando servidor web para dominio: ${DOMAIN}..."

# Asegurar carpeta pública de soporte
mkdir -p /var/www/html/public

echo "==> [Nginx Setup] Servidor listo para escuchar en puerto 80 detrás del proxy de Coolify."
