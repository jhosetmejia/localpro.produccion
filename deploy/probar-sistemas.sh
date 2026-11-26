#!/usr/bin/env bash
# 2026-11-26 (Jhoset): simulacro semanal de PFN y NES: baja de Google Drive (o Backblaze) la copia más reciente de cada uno,
# la descifra y revisa que la base se pueda restaurar (tablas), que estén los archivos y el .env, y cuántas fotos hay copiadas.
# No toca los sistemas en vivo. Cron de los domingos (actualizar.sh) u orden "probar-sistemas". Resultado en el reporte.
set -uo pipefail
EST=/var/lib/localpro/prueba-sistemas; mkdir -p /var/lib/localpro
KEY=/etc/localpro-respaldo.key
tiene() { rclone listremotes 2>/dev/null | grep -qx "$1:"; }
[ -s "$KEY" ] && command -v rclone >/dev/null || { echo "$(date '+%F %T') FALLÓ: la copia externa no está activada" | tee "$EST"; exit 1; }
B2="$(cat /etc/localpro-respaldo.destino 2>/dev/null)"; B2="${B2%/*}"
FUENTES=(); tiene gdrive && FUENTES+=("gdrive:Respaldos LocalPro"); [ -n "$B2" ] && tiene respaldo && FUENTES+=("$B2")
PGR=""; for b in /usr/lib/postgresql/{18,17}/bin/pg_restore; do [ -x "$b" ] && { PGR="$b"; break; }; done
RES=""; OK=1
for S in pfn nes; do
  TMP=$(mktemp -d); R=""
  for F in "${FUENTES[@]}"; do
    DIA=$(rclone lsf --dirs-only "$F/$S" 2>/dev/null | tr -d '/' | grep -E '^[0-9]{4}-' | sort | tail -1)
    [ -n "$DIA" ] && rclone copy "$F/$S/$DIA/" "$TMP/" >/dev/null 2>&1 && ls "$TMP"/base-* >/dev/null 2>&1 && { R="${F%%:*} $DIA"; break; }
  done
  if [ -z "$R" ]; then RES="$RES · $S: FALLÓ (no hay copia afuera)"; OK=0; rm -rf "$TMP"; continue; fi
  MAL=""
  for f in "$TMP"/*.enc; do [ -f "$f" ] || continue
    openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass "file:$KEY" -in "$f" -out "${f%.enc}" 2>/dev/null || MAL="la llave no abre $(basename "$f")"; rm -f "$f"; done
  BASE=$(ls -1t "$TMP"/base-* 2>/dev/null | head -1); NT=0
  case "$BASE" in
    *.sql.gz) gunzip -t "$BASE" 2>/dev/null && NT=$(gunzip -c "$BASE" | grep -c '^CREATE TABLE') ;;
    *.dump) [ -n "$PGR" ] && NT=$("$PGR" -l "$BASE" 2>/dev/null | grep -c ' TABLE ') ;;
  esac
  [ "$NT" -ge 10 ] || MAL="${MAL:-la base no se puede abrir o tiene $NT tablas}"
  ARCH=$(ls -1 "$TMP"/archivos-*.tar.gz 2>/dev/null | head -1); [ -n "$ARCH" ] && ! tar -tzf "$ARCH" >/dev/null 2>&1 && MAL="${MAL:-los archivos están dañados}"
  CONF=$(ls -1 "$TMP"/config-*.tar.gz 2>/dev/null | head -1)
  NF=0; for F in "${FUENTES[@]}"; do for D in "fotos-$S" fotos-pfn; do n=$(rclone size --json "$F/$D" 2>/dev/null | python3 -c 'import sys,json;print(json.load(sys.stdin).get("count",0))' 2>/dev/null); [ "${n:-0}" -gt "$NF" ] && NF=$n; done; done
  if [ -n "$MAL" ]; then RES="$RES · $S: FALLÓ ($MAL)"; OK=0
  else RES="$RES · $S OK ($R): base con $NT tablas, archivos $([ -n "$ARCH" ] && echo sí || echo no), .env $([ -n "$CONF" ] && echo sí || echo no), $NF fotos copiadas"; fi
  rm -rf "$TMP"
done
echo "$(date '+%F %T') $([ $OK = 1 ] && echo OK || echo FALLÓ)$RES" | tee "$EST"
