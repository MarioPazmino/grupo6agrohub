#!/bin/bash

# Define the database and the output directory
DB_NAME="grupo6_agrohub"
OUTPUT_DIR="/var/www/html/grupo6agrohub/backup"
URI="mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=/var/www/html/grupo6agrohub/global-bundle.pem"

# Array of collections
COLLECTIONS=("cosechas" "productos" "siembras" "terrenos" "usuarios")

# Export each collection to JSON
for COLLECTION in "${COLLECTIONS[@]}"; do
    echo "Exporting collection: $COLLECTION"
    OUTPUT_FILE="$OUTPUT_DIR/$COLLECTION.json"
    mongoexport --uri="$URI" --db="$DB_NAME" --collection="$COLLECTION" --out="$OUTPUT_FILE"

    # Enclose the content in square brackets
    sed -i '1s/^/[\n/' "$OUTPUT_FILE" # Add an opening bracket at the beginning
    echo "]" >> "$OUTPUT_FILE"        # Add a closing bracket at the end
done

echo "Backup completed."
