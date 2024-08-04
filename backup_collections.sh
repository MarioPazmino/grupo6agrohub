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
    mongoexport --uri="$URI" --db="$DB_NAME" --collection="$COLLECTION" --out="$OUTPUT_DIR/$COLLECTION.json"
    
    # Add brackets to the JSON file and properly format it as an array
    echo "[" > "$OUTPUT_DIR/$COLLECTION.tmp"
    
    # Remove the first line which is an opening bracket or similar
    sed '1d' "$OUTPUT_DIR/$COLLECTION.json" | \
    # Remove trailing comma from each line
    sed 's/,$//' | \
    # Add commas between lines, except the last line
    awk '{if (NR!=1) printf(","); print}' >> "$OUTPUT_DIR/$COLLECTION.tmp"
    
    # Add closing bracket
    echo "]" >> "$OUTPUT_DIR/$COLLECTION.tmp"
    
    # Replace the original file with the modified one
    mv "$OUTPUT_DIR/$COLLECTION.tmp" "$OUTPUT_DIR/$COLLECTION.json"
done

echo "Backup completed."
