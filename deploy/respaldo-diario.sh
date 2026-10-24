#!/usr/bin/env bash
# Respaldo diario de LocalPro: base de datos + archivos subidos (logos, fotos). Guarda los últimos 10 días.
# Lo ejecuta el cron /etc/cron.d/localpro-respaldo (lo instala deploy/actualizar.sh).
# Desarrollado por Jhoset Mejía - DiCreWeb
set -uo pipefail
# Carpeta del sitio de producción (la que atiende localproo.com)
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
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

# Archivos subidos (fotos, logos) y firmas de los profesionales del consultorio (carpeta privada)
CARPETAS="public"; [ -d "$RAIZ/storage/app/firmas" ] && CARPETAS="$CARPETAS firmas"
tar -czf "$DEST/archivos-$HOY.tar.gz" -C "$RAIZ/storage/app" $CARPETAS 2>/dev/null && echo "$(date '+%F %T') archivos OK"

find "$DEST" -name 'base-*.sql.gz' -mtime +$DIAS -delete
find "$DEST" -name 'archivos-*.tar.gz' -mtime +$DIAS -delete
chown -R "$USR:$USR" "/home/$USR/respaldos"

# Copia fuera del servidor (si ya se activó con deploy/activar-respaldo-externo.sh)
if [ -s /etc/localpro-respaldo.key ] && command -v rclone >/dev/null 2>&1; then
  /bin/bash "$RAIZ/deploy/respaldo-externo.sh" "$DEST/base-$HOY.sql.gz" "$DEST/archivos-$HOY.tar.gz"
fi
