#!/usr/bin/env bash
# Sube la copia del día a un almacenamiento fuera del servidor (Backblaze B2 u otro, vía rclone, remoto "respaldo").
# Los archivos van CIFRADOS (AES-256) con la llave de /etc/localpro-respaldo.key: sin esa llave nadie los puede abrir.
# Se conservan 30 días. Lo llama respaldo-diario.sh. Estado en /var/lib/localpro/respaldo-externo (sale en el reporte).
# Uso: respaldo-externo.sh base.sql.gz archivos.tar.gz
set -uo pipefail
KEY=/etc/localpro-respaldo.key
DESTINO="$(cat /etc/localpro-respaldo.destino 2>/dev/null)"   # ej. respaldo:localpro-respaldos/localpro
EST=/var/lib/localpro/respaldo-externo; mkdir -p /var/lib/localpro
DIA="$(date +%Y-%m-%d)"; TMP="$(mktemp -d)"; ok=1
[ -n "$DESTINO" ] || { echo "$(date '+%F %T') FALLÓ: falta el destino" > "$EST"; exit 1; }
for f in "$@"; do
  [ -s "$f" ] || continue
  if openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$KEY" -in "$f" -out "$TMP/$(basename "$f").enc" \
     && rclone copy --retries 5 --low-level-retries 10 "$TMP/$(basename "$f").enc" "$DESTINO/$DIA/"; then
    echo "$(date '+%F %T') externo OK $(basename "$f")"
  else
    ok=0; echo "$(date '+%F %T') externo ERROR $(basename "$f")"
  fi
done
rm -rf "$TMP"
# Se borran las copias externas de más de 30 días
rclone delete --min-age 30d "$DESTINO" >/dev/null 2>&1; rclone rmdirs --leave-root "$DESTINO" >/dev/null 2>&1
if [ $ok = 1 ]; then echo "$(date '+%F %T') OK" > "$EST"; else echo "$(date '+%F %T') FALLÓ (ver /var/log/localpro-respaldo.log)" > "$EST"; fi
