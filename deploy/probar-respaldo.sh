#!/usr/bin/env bash
# Simulacro semanal: baja de Backblaze la copia más reciente, la descifra y revisa que se pueda restaurar
# (la base completa con sus tablas principales, las fotos y la configuración). No toca el sistema en vivo.
# Lo corre el cron de los domingos (lo instala actualizar.sh) o "localproo probar-respaldo". Resultado en el reporte.
set -uo pipefail
EST=/var/lib/localpro/prueba-respaldo; mkdir -p /var/lib/localpro
fallo() { echo "$(date '+%F %T') FALLÓ: $1" | tee "$EST"; rm -rf "$TMP"; exit 1; }
TMP=$(mktemp -d)
[ -s /etc/localpro-respaldo.key ] && command -v rclone >/dev/null || fallo "la copia externa no está activada"
DESTINO="$(cat /etc/localpro-respaldo.destino)"
DIA=$(rclone lsf --dirs-only "$DESTINO" 2>/dev/null | tr -d '/' | grep -E '^[0-9]{4}-' | sort | tail -1)
[ -n "$DIA" ] || fallo "no hay copias en Backblaze"
rclone copy "$DESTINO/$DIA/" "$TMP/" >/dev/null 2>&1 || fallo "no se pudo bajar la copia del $DIA"
for f in "$TMP"/*.enc; do [ -f "$f" ] || continue
  openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass file:/etc/localpro-respaldo.key -in "$f" -out "${f%.enc}" 2>/dev/null || fallo "la llave no abre $(basename "$f")"
  rm -f "$f"; done
# La más reciente según la hora del nombre (base-AAAAMMDD-HHMM; la de las 3:15 a. m. no lleva hora)
clave() { local n; n=$(basename "$1" .sql.gz); n=${n#base-}; local h=${n:9:4}; echo "${n:0:8}${h:-0315}"; }
BASE=$(for f in "$TMP"/base-*.sql.gz; do [ -f "$f" ] && echo "$(clave "$f") $f"; done | sort | tail -1 | cut -d' ' -f2-)
[ -n "$BASE" ] && gunzip -t "$BASE" 2>/dev/null || fallo "la base del $DIA está dañada o no está"
# Se descomprime una vez y se revisa el archivo (leer con "grep -q" directo del gunzip corta la lectura y daba falsa alarma)
gunzip -c "$BASE" | grep '^CREATE TABLE' > "$TMP/tablas.txt"
NT=$(wc -l < "$TMP/tablas.txt")
for T in tiendas users productos producto_variantes pos_ventas cajas; do
  grep -q "^CREATE TABLE \`$T\`" "$TMP/tablas.txt" || fallo "a la base le falta la tabla $T"
done
[ "$NT" -ge 50 ] || fallo "la base solo tiene $NT tablas"
ARCH=$(ls -1 "$TMP"/archivos-*.tar.gz 2>/dev/null | head -1); NF=0
if [ -n "$ARCH" ]; then
  tar -tzf "$ARCH" > "$TMP/fotos.txt" 2>/dev/null || fallo "el archivo de fotos está dañado"
  NF=$(grep -c '\.[a-zA-Z]\{3,4\}$' "$TMP/fotos.txt" || true)
fi
CONF=$(ls -1 "$TMP"/config-*.tar.gz 2>/dev/null | head -1)
echo "$(date '+%F %T') OK · copia del $DIA: base con $NT tablas ($(du -h "$BASE" | cut -f1)) · $NF archivos de fotos · configuración: $([ -n "$CONF" ] && echo sí || echo 'no (sale desde la próxima copia nocturna)')" | tee "$EST"
rm -rf "$TMP"
