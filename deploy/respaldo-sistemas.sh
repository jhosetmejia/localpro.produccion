#!/usr/bin/env bash
# 2026-11-25 (Jhoset): respaldo de PFN (pastofightnight.com) y NES (narinoexposhow.com), igual que el de LocalPro.
# Lo corren los crons de /etc/cron.d/localpro-respaldo (los instala deploy/actualizar.sh):
#   respaldo-sistemas.sh          (3:40 a. m.) base de datos + archivos del servidor (storage/app) + .env + fotos de Backblaze
#   respaldo-sistemas.sh --rapido (cada 6 horas) solo las bases de datos
# Base: MySQL (mysqldump) o Postgres/Neon (pg_dump 17). En el servidor: bases 7 días, archivos 3 días.
# Afuera, CIFRADO con la llave de LocalPro (/etc/localpro-respaldo.key): Backblaze ("respaldo", si se activó) y Google Drive ("gdrive",
# si se activó con deploy/activar-google-drive.sh), 30 días. Las fotos de Backblaze de cada sistema se copian (solo las nuevas, nunca
# se borran allá) a Google Drive; si no hay Drive, al Backblaze de respaldo.
# Estado en /var/lib/localpro/respaldo-sistemas (sale en el reporte del servidor).
set -uo pipefail
RAPIDO=0; [ "${1:-}" = "--rapido" ] && RAPIDO=1
EST=/var/lib/localpro/respaldo-sistemas; mkdir -p /var/lib/localpro
KEY=/etc/localpro-respaldo.key
B2DEST="$(cat /etc/localpro-respaldo.destino 2>/dev/null)"; B2DEST="${B2DEST%/*}"   # respaldo:bucket
GD="Respaldos LocalPro"
HOY="$(date +%Y%m%d)"; NOM="$HOY"; [ $RAPIDO = 1 ] && NOM="$HOY-$(date +%H%M)"; DIA="$(date +%Y-%m-%d)"
log() { echo "$(date '+%F %T') [sistemas] $*"; }
envv() { grep -E "^$2=" "$1/.env" 2>/dev/null | tail -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/; s/^'"'"'(.*)'"'"'$/\1/'; }
raiz() { for d in /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs "^APP_URL=\"?https?://(www\.)?$1/?\"?$" "$d/.env" && { echo "$d"; return; }; done; }
tiene() { command -v rclone >/dev/null 2>&1 && rclone listremotes 2>/dev/null | grep -qx "$1:"; }
command -v rclone >/dev/null 2>&1 || curl -fsSL https://rclone.org/install.sh | bash >/dev/null 2>&1

# pg_dump 17 (Neon usa Postgres 17; el pg_dump viejo de Ubuntu no sirve)
pgdump() {
  local b; for b in /usr/lib/postgresql/{18,17}/bin/pg_dump; do [ -x "$b" ] && { echo "$b"; return; }; done
  log "instalando pg_dump 17"
  export DEBIAN_FRONTEND=noninteractive
  apt-get install -y -qq curl ca-certificates lsb-release >/dev/null 2>&1
  install -d /usr/share/postgresql-common/pgdg
  curl -fsSo /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc https://www.postgresql.org/media/keys/ACCC4CF8.asc
  echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list
  apt-get update -qq >/dev/null 2>&1 && apt-get install -y -qq postgresql-client-17 >/dev/null 2>&1
  [ -x /usr/lib/postgresql/17/bin/pg_dump ] && echo /usr/lib/postgresql/17/bin/pg_dump
}

# Sube un archivo cifrado a Backblaze y a Google Drive (los que estén activos)
subir() {
  local f="$1" sis="$2" tmp; [ -s "$f" ] && [ -s "$KEY" ] || return 0
  tmp="$(mktemp -d)"
  if openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" -in "$f" -out "$tmp/$(basename "$f").enc"; then
    if [ -n "$B2DEST" ] && tiene respaldo; then
      rclone copy --retries 5 "$tmp/$(basename "$f").enc" "$B2DEST/$sis/$DIA/" && log "$sis Backblaze OK $(basename "$f")" || { log "$sis Backblaze ERROR $(basename "$f")"; OK=0; }
    fi
    if tiene gdrive; then
      rclone copy --retries 5 "$tmp/$(basename "$f").enc" "gdrive:$GD/$sis/$DIA/" && log "$sis Drive OK $(basename "$f")" || { log "$sis Drive ERROR $(basename "$f")"; OK=0; }
    fi
  fi
  rm -rf "$tmp"
}

OK=1; RES=""
for PAR in "pfn pastofightnight\.com" "nes narinoexposhow\.com"; do
  SIS="${PAR%% *}"; DOM="${PAR#* }"
  R="$(raiz "$DOM")"
  if [ -z "$R" ]; then log "$SIS: no está en este servidor"; RES="$RES $SIS=no-esta"; continue; fi
  USR="$(stat -c %U "$R")"; DEST="/home/$USR/respaldos/diario"; mkdir -p "$DEST"
  CON="$(envv "$R" DB_CONNECTION)"; DBN="$(envv "$R" DB_DATABASE)"; DBU="$(envv "$R" DB_USERNAME)"; DBP="$(envv "$R" DB_PASSWORD)"
  DBH="$(envv "$R" DB_HOST)"; DBPORT="$(envv "$R" DB_PORT)"; DBURL="$(envv "$R" DB_URL)"; [ -z "$DBURL" ] && DBURL="$(envv "$R" DATABASE_URL)"
  F="$DEST/base-$NOM"; BOK=0
  if [ "$CON" = "pgsql" ]; then
    PGD="$(pgdump)"
    if [ -n "$PGD" ]; then
      if [ -n "$DBURL" ]; then
        "$PGD" --no-owner --no-acl -Fc -f "$F.dump.tmp" "$DBURL" && BOK=1
      else
        PGPASSWORD="$DBP" PGSSLMODE=require "$PGD" -h "$DBH" -p "${DBPORT:-5432}" -U "$DBU" --no-owner --no-acl -Fc -f "$F.dump.tmp" "$DBN" && BOK=1
      fi
    fi
    [ $BOK = 1 ] && [ -s "$F.dump.tmp" ] && mv "$F.dump.tmp" "$F.dump" && F="$F.dump" || { BOK=0; rm -f "$F.dump.tmp"; }
  else
    MYSQL_PWD="$DBP" mysqldump -h "${DBH:-127.0.0.1}" -P "${DBPORT:-3306}" -u "$DBU" --single-transaction --quick --routines --no-tablespaces "$DBN" 2>/dev/null | gzip > "$F.sql.gz.tmp" \
      && gunzip -t "$F.sql.gz.tmp" 2>/dev/null && [ "$(stat -c %s "$F.sql.gz.tmp")" -gt 1000 ] && mv "$F.sql.gz.tmp" "$F.sql.gz" && F="$F.sql.gz" && BOK=1
    [ $BOK = 1 ] || rm -f "$F.sql.gz.tmp"
  fi
  if [ $BOK = 1 ]; then log "$SIS base OK $(du -h "$F" | cut -f1)"; subir "$F" "$SIS"; RES="$RES $SIS-base=OK"
  else log "$SIS ERROR respaldando la base ($CON)"; OK=0; RES="$RES $SIS-base=FALLÓ"; fi

  if [ $RAPIDO = 0 ]; then
    # Archivos del servidor (fotos del chat, imágenes subidas, ajustes) sin las cachés que se rearman solas
    tar -czf "$DEST/archivos-$HOY.tar.gz" --exclude='*cache*' --exclude='video-tmp' --exclude='flyer-fotos' --exclude='livewire-tmp' -C "$R/storage" app 2>/dev/null \
      && { log "$SIS archivos OK $(du -h "$DEST/archivos-$HOY.tar.gz" | cut -f1)"; subir "$DEST/archivos-$HOY.tar.gz" "$SIS"; }
    ( umask 077; tar -czf "$DEST/config-$HOY.tar.gz" -C "$R" .env ) && chown root:root "$DEST/config-$HOY.tar.gz" && subir "$DEST/config-$HOY.tar.gz" "$SIS"
    # Fotos y archivos que están en Backblaze (S3): se copian solo los nuevos; lo que se borre allá se queda en la copia
    export RCLONE_S3_PROVIDER=Other RCLONE_S3_ACCESS_KEY_ID="$(envv "$R" B2_KEY_ID)" RCLONE_S3_SECRET_ACCESS_KEY="$(envv "$R" B2_APPLICATION_KEY)" \
      RCLONE_S3_ENDPOINT="$(envv "$R" B2_ENDPOINT)" RCLONE_S3_REGION="$(envv "$R" B2_REGION)"
    BUCKET="$(envv "$R" B2_BUCKET)"
    if [ -n "$BUCKET" ] && [ -n "$RCLONE_S3_ACCESS_KEY_ID" ]; then
      FD=""; tiene gdrive && FD="gdrive:$GD/fotos-$SIS"; [ -z "$FD" ] && [ -n "$B2DEST" ] && tiene respaldo && FD="$B2DEST/fotos-$SIS"
      if [ -n "$FD" ]; then
        if rclone copy --transfers 8 --checkers 16 --retries 3 --exclude 'video-tmp/**' ":s3:$BUCKET" "$FD" 2>>/var/log/localpro-respaldo.log; then
          log "$SIS fotos OK → ${FD%%:*}"; RES="$RES $SIS-fotos=OK"
        else log "$SIS fotos ERROR"; OK=0; RES="$RES $SIS-fotos=FALLÓ"; fi
      else RES="$RES $SIS-fotos=sin-destino"; fi
    fi
    unset RCLONE_S3_PROVIDER RCLONE_S3_ACCESS_KEY_ID RCLONE_S3_SECRET_ACCESS_KEY RCLONE_S3_ENDPOINT RCLONE_S3_REGION
  fi
  find "$DEST" -name 'base-*-*.sql.gz' -mtime +2 -delete; find "$DEST" -name 'base-*-*.dump' -mtime +2 -delete
  find "$DEST" \( -name 'base-*.sql.gz' -o -name 'base-*.dump' -o -name 'config-*.tar.gz' \) -mtime +7 -delete
  find "$DEST" -name 'archivos-*.tar.gz' -mtime +3 -delete
  find "$DEST" ! -name 'config-*' -exec chown "$USR:$USR" {} + 2>/dev/null
done

# Copias externas de más de 30 días (no toca las carpetas de fotos)
for S in pfn nes; do
  [ -n "$B2DEST" ] && tiene respaldo && rclone delete --min-age 30d "$B2DEST/$S" >/dev/null 2>&1
  tiene gdrive && rclone delete --min-age 30d "gdrive:$GD/$S" >/dev/null 2>&1
done
FUERA="solo en el servidor"; { [ -n "$B2DEST" ] && tiene respaldo; } && FUERA="Backblaze"; tiene gdrive && FUERA="$FUERA + Google Drive"; [ -s "$KEY" ] || FUERA="solo en el servidor (falta la llave de cifrado)"
echo "$(date '+%F %T') $([ $OK = 1 ] && echo OK || echo 'FALLÓ (ver /var/log/localpro-respaldo.log)') ·$RES · afuera: $FUERA" > "$EST"
log "listo:$RES"
