#!/bin/sh
set -e

DOMAIN="${DOMAIN:-localhost}"
CERT_DIR="/etc/letsencrypt/live/${DOMAIN}"
CERT_FILE="${CERT_DIR}/fullchain.pem"
KEY_FILE="${CERT_DIR}/privkey.pem"

echo "==> [Nginx SSL Setup] Verificando certificados para dominio: ${DOMAIN}..."

# 1. Crear carpeta de webroot para certbot
mkdir -p /var/www/certbot

# 2. Si no existen certificados válidos, generar un certificado autofirmado provisional
# Esto evita que Nginx falle al arrancar antes de que Certbot obtenga el certificado real de Let's Encrypt
if [ ! -f "$CERT_FILE" ] || [ ! -f "$KEY_FILE" ]; then
    echo "==> [Nginx SSL Setup] Certificado no encontrado en ${CERT_DIR}. Generando certificado autofirmado provisional..."
    mkdir -p "$CERT_DIR"
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$KEY_FILE" \
        -out "$CERT_FILE" \
        -subj "/C=GT/ST=Guatemala/L=Guatemala/O=Iglesia Getsemani/CN=${DOMAIN}" >/dev/null 2>&1
    echo "==> [Nginx SSL Setup] Certificado autofirmado generado exitosamente en ${CERT_DIR}."
else
    echo "==> [Nginx SSL Setup] Certificado existente encontrado en ${CERT_DIR}."
fi

# 3. Soporte para pruebas locales en HTTP (Prompt P21 §6: respuesta directa en http://localhost)
if [ "$REDIRECT_TO_HTTPS" = "false" ]; then
    echo "==> [Nginx SSL Setup] REDIRECT_TO_HTTPS=false: Configurando puerto 80 para responder directamente en HTTP..."
    cat <<'EOF' > /etc/nginx/templates/default.conf.template
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    root /var/www/html/public;
    index index.php index.html;
    charset utf-8;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript image/svg+xml;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60s;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name ${DOMAIN};

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers on;

    root /var/www/html/public;
    index index.php index.html;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
fi
