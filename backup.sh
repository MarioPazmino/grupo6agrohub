#!/bin/bash

# Comando para realizar el backup de la base de datos 'grupo6_agrohub' desde Amazon DocumentDB
mongodump --ssl \
  --host testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017 \
  --sslCAFile /var/www/html/grupo6agrohub/global-bundle.pem \
  --username mario1010 \
  --password marito10 \
  --db grupo6_agrohub \
  --out /var/www/html/grupo6agrohub/backup_$(date +\%F)


