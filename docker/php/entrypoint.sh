#!/bin/bash
set -e

# Instalar dependencias de Composer si existe composer.json
if [ -f "composer.json" ]; then
    composer install --no-interaction --optimize-autoloader
fi

exec "$@"
