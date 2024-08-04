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
    
    # Add brackets to the JSON file to make it an array
    # Create a temporary file to hold the formatted JSON
    TEMP_FILE="$OUTPUT_DIR/$COLLECTION.tmp"
    
    # Start the JSON array
    echo "[" > "$TEMP_FILE"
    
    # Add each line, except the first one (which does not need a leading comma)
    # and handle the trailing comma for the last item
    awk 'NR > 1 {print (NR==2 ? "" : ",") $0}' "$OUTPUT_DIR/$COLLECTION.json" >> "$TEMP_FILE"
    
    # End the JSON array
    echo "]" >> "$TEMP_FILE"
    
    # Replace the original file with the modified one
    mv "$TEMP_FILE" "$OUTPUT_DIR/$COLLECTION.json"
done

echo "Backup completed."
