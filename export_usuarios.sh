#!/bin/bash

# Define the database and the output directory
DB_NAME="grupo6_agrohub"
OUTPUT_DIR="/var/www/html/grupo6agrohub/backup"
URI="mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=/var/www/html/grupo6agrohub/global-bundle.pem"

# Define the path to jq
JQ_PATH="/usr/bin/jq"  # Cambia esto si jq está en una ubicación diferente

# Define the collection name
COLLECTION="usuarios"
echo "Collection: $COLLECTION"

# Export the usuarios collection to JSON
echo "Exporting collection: $COLLECTION"
mongoexport --uri="$URI" --db="$DB_NAME" --collection="$COLLECTION" --jsonArray --out="$OUTPUT_DIR/$COLLECTION.json"

# Check if mongoexport was successful
if [ $? -ne 0 ]; then
    echo "mongoexport failed"
    exit 1
fi

# Check if jq is installed
if ! command -v "$JQ_PATH" &> /dev/null
then
    echo "jq command not found. Please install jq."
    exit 1
fi

"$JQ_PATH" 'map({
    _id: ._id.$oid // O simplemente ._id si no es un objeto anidado
    nombre: .nombre,
    apellido: .apellido,
    email: .email,
    telefono: .telefono,
    cedula: .cedula,
    rol: .rol,
    fecha_contratacion: .fecha_contratacion, // Ajusta si es necesario
    tareas_asignadas: (.tareas_asignadas // [] | map({
        tarea_id: .tarea_id // Ajusta si es necesario
        descripcion: .descripcion,
        estado: .estado
    })),
    password: .password,
    nombre_usuario: .nombre_usuario
})' "$OUTPUT_DIR/$COLLECTION.json" > "$OUTPUT_DIR/$COLLECTION.tmp"


# Replace the original file with the modified one
mv "$OUTPUT_DIR/$COLLECTION.tmp" "$OUTPUT_DIR/$COLLECTION.json"

echo "Backup completed."
