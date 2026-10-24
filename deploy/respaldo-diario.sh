#!/usr/bin/env bash
# Respaldo de LocalPro. Lo ejecutan los crons de /etc/cron.d/localpro-respaldo (los instala deploy/actualizar.sh):
#   respaldo-diario.sh          (3:15 a. m.) base de datos + archivos subidos (fotos, logos, firmas) + configuración (.env)
#   respaldo-diario.sh --rapido (cada 4 horas) solo la base de datos, para no perder más de unas horas de ventas
# En el servidor: base 10 días (copias rápidas 3 días), fotos/videos 3 días (pesan mucho). Recorta el log si pasa de 50 MB. Afuera (Backblaze, cifradas) 30 días.
# Desarrollado por Jhoset Mejía - DiCreWeb
set -uo pipefail
RAPIDO=0; [ "${1:-}" = "--rapido" ] && RAPIDO=1
# Carpeta del sitio de producción (la que atiende localproo.com)
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
[ -n "$RAIZ" ] || { echo "$(date '+%F %T') ERROR: no encontré la carpeta de LocalProo"; exit 1; }
USR="$(stat -c %U "$RAIZ")"
DEST="/home/$USR/respaldos/diario"
DIAS=10
DIAS_ARCH=3
HOY="$(date +%Y%m%d)"
NOM="$HOY"; [ $RAPIDO = 1 ] && NOM="$HOY-$(date +%H%M)"
mkdir -p "$DEST"

DBN=$(grep ^DB_DATABASE= "$RAIZ/.env" | cut -d= -f2-)
DBU=$(grep ^DB_USERNAME= "$RAIZ/.env" | cut -d= -f2-)
DBP=$(grep ^DB_PASSWORD= "$RAIZ/.env" | cut -d= -f2-)

SUBIR=()
if MYSQL_PWD="$DBP" mysqldump -u "$DBU" --single-transaction --quick --routines --no-tablespaces "$DBN" | gzip > "$DEST/base-$NOM.sql.gz.tmp" \
   && gunzip -t "$DEST/base-$NOM.sql.gz.tmp" 2>/dev/null; then
  mv "$DEST/base-$NOM.sql.gz.tmp" "$DEST/base-$NOM.sql.gz"
  SUBIR+=("$DEST/base-$NOM.sql.gz")
  echo "$(date '+%F %T') base OK $(du -h "$DEST/base-$NOM.sql.gz" | cut -f1)"
else
  rm -f "$DEST/base-$NOM.sql.gz.tmp"
  echo "$(date '+%F %T') ERROR respaldando la base"
fi

if [ $RAPIDO = 0 ]; then
  # Archivos subidos (fotos, logos) y firmas de los profesionales del consultorio (carpeta privada)
  CARPETAS="public"; [ -d "$RAIZ/storage/app/firmas" ] && CARPETAS="$CARPETAS firmas"
  tar -czf "$DEST/archivos-$HOY.tar.gz" -C "$RAIZ/storage/app" $CARPETAS 2>/dev/null && SUBIR+=("$DEST/archivos-$HOY.tar.gz") && echo "$(date '+%F %T') archivos OK"
  # Configuración: .env (llave interna de Laravel, Wompi, Meta, DIAN). Sin ella los datos cifrados de la base
  # (credenciales de facturación) no se pueden abrir en un servidor nuevo. Solo la lee root.
  ( umask 077; tar -czf "$DEST/config-$HOY.tar.gz" -C "$RAIZ" .env ) && chmod 600 "$DEST/config-$HOY.tar.gz" && chown root:root "$DEST/config-$HOY.tar.gz" \
    && SUBIR+=("$DEST/config-$HOY.tar.gz") && echo "$(date '+%F %T') configuración OK"
fi

find "$DEST" -name 'base-*-*.sql.gz' -mtime +2 -delete
find "$DEST" -name 'base-*.sql.gz' -mtime +$DIAS -delete
find "$DEST" -name 'archivos-*.tar.gz' -mtime +$DIAS_ARCH -delete
find "$DEST" -name 'config-*.tar.gz' -mtime +$DIAS -delete
# Log de Laravel: si pasa de 50 MB se dejan solo las últimas 20.000 líneas
LOG="$RAIZ/storage/logs/laravel.log"
if [ -f "$LOG" ] && [ "$(stat -c %s "$LOG")" -gt 52428800 ]; then
  tail -n 20000 "$LOG" > "$LOG.tmp" && cat "$LOG.tmp" > "$LOG" && rm -f "$LOG.tmp"
  echo "$(date '+%F %T') log recortado"
fi
chown "$USR:$USR" "/home/$USR/respaldos" 2>/dev/null
find "/home/$USR/respaldos" ! -name 'config-*' -exec chown "$USR:$USR" {} + 2>/dev/null

# Copia fuera del servidor (si ya se activó con deploy/activar-respaldo-externo.sh)
if [ ${#SUBIR[@]} -gt 0 ] && [ -s /etc/localpro-respaldo.key ] && command -v rclone >/dev/null 2>&1; then
  /bin/bash "$RAIZ/deploy/respaldo-externo.sh" "${SUBIR[@]}"
fi
