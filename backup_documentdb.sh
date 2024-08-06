#!/bin/bash

# Define la base de datos y el directorio de salida
DB_NAME="grupo6_agrohub"  # Cambia esto si necesitas respaldar más de una base de datos
OUTPUT_DIR="/var/www/html/grupo6agrohub/backup"
URI="mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=/var/www/html/grupo6agrohub/global-bundle.pem"

# Crea el directorio de salida si no existe
mkdir -p "$OUTPUT_DIR"

# Ejecuta mongodump para hacer el respaldo de toda la base de datos
echo "Realizando el respaldo de la base de datos $DB_NAME..."
mongodump --uri="$URI" --db="$DB_NAME" --out="$OUTPUT_DIR"

echo "Backup completado y guardado en $OUTPUT_DIR."
