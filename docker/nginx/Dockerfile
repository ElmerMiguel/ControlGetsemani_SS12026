# ==============================================================================
# Dockerfile Nginx — Sistema de Finanzas e Inventario Iglesia Getsemaní
# Reglas: ARQUITECTURA_FASE3.md §6 | Prompt P21
# ==============================================================================

FROM nginx:1.27-alpine

LABEL maintainer="Elmer Wilson <clave13@seminario.edu>"
LABEL description="Getsemani Finanzas - Nginx Reverse Proxy y SSL"

# Instalar openssl para generación de certificados de respaldo y curl/bash
RUN apk add --no-cache openssl bash curl

# Plantilla oficial de Nginx procesada por envsubst nativo
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template

# Script de verificación y preparación de certificados SSL previo al arranque de Nginx
COPY docker/nginx/ssl-setup.sh /docker-entrypoint.d/10-ssl-setup.sh
RUN chmod +x /docker-entrypoint.d/10-ssl-setup.sh

# Filtrar para que envsubst solo reemplace DOMAIN (evita corromper $host, $uri, etc.)
ENV NGINX_ENVSUBST_FILTER="DOMAIN"

EXPOSE 80
