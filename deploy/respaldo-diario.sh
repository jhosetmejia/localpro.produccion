#!/usr/bin/env bash
# Respaldo diario de LocalPro: base de datos + archivos subidos (logos, fotos). Guarda los últimos 10 días.
# Lo ejecuta el cron /etc/cron.d/localpro-respaldo (lo instala deploy/actualizar.sh).
# Desarrollado por Jhoset Mejía - DiCreWeb
set -uo pipefail
RAIZ="$( [ -d /home/localproo/htdocs/localproo.com ] && echo /home/localproo/htdocs/localproo.com || echo /home/localpro/htdocs/localpro.narinoexposhow.com )"
USR="$(stat -c %U "$RAIZ")"
DEST="/home/$USR/respaldos/diario"
DIAS=10
HOY="$(date +%Y%m%d)"
mkdir -p "$DEST"

DBN=$(grep ^DB_DATABASE= "$RAIZ/.env" | cut -d= -f2-)
DBU=$(grep ^DB_USERNAME= "$RAIZ/.env" | cut -d= -f2-)
DBP=$(grep ^DB_PASSWORD= "$RAIZ/.env" | cut -d= -f2-)

if MYSQL_PWD="$DBP" mysqldump -u "$DBU" --single-transaction --no-tablespaces "$DBN" | gzip > "$DEST/base-$HOY.sql.gz.tmp"; then
  mv "$DEST/base-$HOY.sql.gz.tmp" "$DEST/base-$HOY.sql.gz"
  echo "$(date '+%F %T') base OK $(du -h "$DEST/base-$HOY.sql.gz" | cut -f1)"
else
  rm -f "$DEST/base-$HOY.sql.gz.tmp"
  echo "$(date '+%F %T') ERROR respaldando la base"
fi

tar -czf "$DEST/archivos-$HOY.tar.gz" -C "$RAIZ/storage/app" public 2>/dev/null && echo "$(date '+%F %T') archivos OK"

find "$DEST" -name 'base-*.sql.gz' -mtime +$DIAS -delete
find "$DEST" -name 'archivos-*.tar.gz' -mtime +$DIAS -delete
chown -R "$USR:$USR" "/home/$USR/respaldos"
