#!/bin/bash

# Define the database and the output directory
DB_NAME="grupo6_agrohub"
OUTPUT_DIR="/var/www/html/grupo6agrohub/backup"
URI="mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=/var/www/html/grupo6agrohub/global-bundle.pem"

# Array of collections
COLLECTIONS=("cosechas" "productos" "siembras" "terrenos" "usuarios")

# Export and process each collection
for COLLECTION in "${COLLECTIONS[@]}"; do
    echo "Exporting collection: $COLLECTION"
    mongoexport --uri="$URI" --db="$DB_NAME" --collection="$COLLECTION" --jsonArray --out="$OUTPUT_DIR/$COLLECTION.json"
    
    # Process the JSON file with jq based on collection type
    case "$COLLECTION" in
        "cosechas")
            jq '[.[] | 
                {
                    _id: ._id["$oid"],
                    siembra_id: .siembra_id["$oid"],
                    fecha_cosecha: .fecha_cosecha["$date"],
                    cantidad,
                    unidad,
                    detalles_cosecha
                }
            ]' "$OUTPUT_DIR/$COLLECTION.json" > "$OUTPUT_DIR/$COLLECTION.tmp"
            ;;
        "productos")
            jq 'map({
                _id: ._id["$oid"],
                nombre: .nombre,
                descripcion: .descripcion,
                tipo: .tipo,
                precio_unitario: .precio_unitario,
                unidad: .unidad,
                variedades: (.variedades | map({
                    nombre_variedad: .nombre_variedad,
                    caracteristicas: .caracteristicas
                }))
            })' "$OUTPUT_DIR/$COLLECTION.json" > "$OUTPUT_DIR/$COLLECTION.tmp"
            ;;
        "siembras")
            jq 'map({
                _id: ._id["$oid"],
                empleado_id: .empleado_id["$oid"],
                terreno_id: .terreno_id["$oid"],
                producto_id: .producto_id["$oid"],
                fecha_siembra: .fecha_siembra["$date"],
                estado: .estado
            })' "$OUTPUT_DIR/$COLLECTION.json" > "$OUTPUT_DIR/$COLLECTION.tmp"
            ;;
        "usuarios")
            jq 'map({
                _id: ._id["$oid"],
                nombre: .nombre,
                apellido: .apellido,
                email: .email,
                telefono: .telefono,
                cedula: .cedula,
                rol: .rol,
                fecha_contratacion: .fecha_contratacion["$date"],
                tareas_asignadas: map({
                    tarea_id: .tarea_id["$oid"],
                    descripcion: .descripcion,
                    estado: .estado
                }),
                password: .password,
                nombre_usuario: .nombre_usuario
            })' "$OUTPUT_DIR/$COLLECTION.json" > "$OUTPUT_DIR/$COLLECTION.tmp"
            ;;
        "terrenos")
            jq 'map({
                _id: ._id["$oid"],
                nombre: .nombre,
                ubicacion: .ubicacion,
                tamano: .tamano,
                estado: .estado,
                descripcion: .descripcion
            })' "$OUTPUT_DIR/$COLLECTION.json" > "$OUTPUT_DIR/$COLLECTION.tmp"
            ;;
        *)
            echo "No processing rules defined for collection: $COLLECTION"
            ;;
    esac
    
    # Replace the original file with the modified one
    mv "$OUTPUT_DIR/$COLLECTION.tmp" "$OUTPUT_DIR/$COLLECTION.json"
done

echo "Backup completed."
