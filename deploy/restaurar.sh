#!/usr/bin/env bash
# Devuelve LocalProo (en ESTE servidor) a como estaba en una copia de seguridad.
# Uso (como root en la Consola web de hPanel):
#   localproo restaurar              → la copia más reciente (incluye las de cada 4 horas)
#   localproo restaurar 2026-10-25   → la última copia de ese día
# Antes de tocar nada guarda una copia de cómo está ahora (por si se arrepiente) y pide escribir RESTAURAR.
# La base vuelve a ese momento; las fotos que falten se recuperan (las fotos nuevas no se borran). El .env no se toca.
set -uo pipefail
[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
[ -n "$RAIZ" ] || { echo "No encontré la carpeta de LocalProo."; exit 1; }
USR="$(stat -c %U "$RAIZ")"; PHP="php8.2"; run() { sudo -u "$USR" "$@"; }
DIARIO="/home/$USR/respaldos/diario"
DIA="${1:-}"; [ -n "$DIA" ] && ! [[ "$DIA" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}$ ]] && { echo "Escriba la fecha así: 2026-10-25"; exit 1; }
PAT="${DIA//-/}"
STAMP="$(date +%Y%m%d-%H%M%S)"
# La hora de cada copia sale del nombre: base-20261026-1645.sql.gz (cada 4 horas) o base-20261026.sql.gz (la de las 3:15 a. m.)
clave() { local n; n=$(basename "$1" .sql.gz); n=${n#base-}; local h=${n:9:4}; echo "${n:0:8}${h:-0315}"; }
ultima() { for f in "$@"; do [ -f "$f" ] && echo "$(clave "$f") $f"; done | sort | tail -1 | cut -d' ' -f2-; }

# 1) Buscar la copia: primero en el servidor, si no, afuera (Backblaze)
BASE=$(ultima "$DIARIO"/base-${PAT}*.sql.gz)
ARCH=""
if [ -n "$BASE" ]; then
  D8=$(basename "$BASE" | cut -c6-13); ARCH=$(ls -1 "$DIARIO/archivos-$D8.tar.gz" 2>/dev/null | head -1)
  ORIGEN="el servidor"
else
  [ -s /etc/localpro-respaldo.key ] && command -v rclone >/dev/null || { echo "No hay copia de ${DIA:-hoy} en el servidor y la copia externa no está activada."; exit 1; }
  DESTINO="$(cat /etc/localpro-respaldo.destino)"
  [ -n "$DIA" ] || DIA=$(rclone lsf --dirs-only "$DESTINO" 2>/dev/null | tr -d '/' | grep -E '^[0-9]{4}-' | sort | tail -1)
  [ -n "$DIA" ] || { echo "No encontré copias en Backblaze."; exit 1; }
  echo "==> Bajando la copia del $DIA de Backblaze"
  bash "$RAIZ/deploy/restaurar-respaldo-externo.sh" "$DIA" >/dev/null || { echo "No hay copia externa del $DIA."; exit 1; }
  BASE=$(ultima /root/restaurar/$DIA/base-*.sql.gz); ARCH=$(ls -1 /root/restaurar/$DIA/archivos-*.tar.gz 2>/dev/null | head -1)
  ORIGEN="Backblaze"
fi
[ -n "$BASE" ] && gunzip -t "$BASE" 2>/dev/null || { echo "La copia de la base no sirve o no existe. No toqué nada."; exit 1; }

echo
echo "  Copia elegida ($ORIGEN): $(basename "$BASE")  ·  $(du -h "$BASE" | cut -f1)  ·  de las $(K=$(clave "$BASE"); echo "${K:6:2}/${K:4:2}/${K:0:4} ${K:8:2}:${K:10:2}")"
[ -n "$ARCH" ] && echo "  Fotos y archivos: $(basename "$ARCH")"
echo
echo "  OJO: todo lo que se haya vendido o cambiado DESPUÉS de esa hora se pierde."
echo "  (Antes se guarda una copia de cómo está ahora, por si acaso.)"
read -r -p "  Para seguir escriba RESTAURAR: " OK < /dev/tty
[ "$OK" = "RESTAURAR" ] || { echo "Cancelado. No se tocó nada."; exit 0; }

DBN=$(grep ^DB_DATABASE= "$RAIZ/.env" | cut -d= -f2-); DBU=$(grep ^DB_USERNAME= "$RAIZ/.env" | cut -d= -f2-); DBP=$(grep ^DB_PASSWORD= "$RAIZ/.env" | cut -d= -f2-)
ANTES="/home/$USR/respaldos/antes-de-restaurar-$STAMP"; mkdir -p "$ANTES"
echo "==> 1/4 Guardando cómo está ahora en $ANTES"
MYSQL_PWD="$DBP" mysqldump -u "$DBU" --single-transaction --quick --routines --no-tablespaces "$DBN" | gzip > "$ANTES/base.sql.gz" || { echo "No se pudo guardar el estado actual. No toqué nada."; exit 1; }
chown -R "$USR:$USR" "$ANTES"

echo "==> 2/4 Sistema en mantenimiento y base restaurada"
run $PHP "$RAIZ/artisan" down --retry=60 >/dev/null 2>&1
# Se vacía la base para que no queden tablas de después de la copia
TABLAS=$(MYSQL_PWD="$DBP" mysql -u "$DBU" -N -e "SELECT GROUP_CONCAT(CONCAT('\`',table_name,'\`')) FROM information_schema.tables WHERE table_schema='$DBN'")
[ -n "$TABLAS" ] && [ "$TABLAS" != "NULL" ] && MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN" -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE $TABLAS; SET FOREIGN_KEY_CHECKS=1;"
if ! gunzip -c "$BASE" | MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN"; then
  echo "!! Falló. Volviendo a como estaba..."
  gunzip -c "$ANTES/base.sql.gz" | MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN"
  run $PHP "$RAIZ/artisan" up >/dev/null 2>&1
  echo "!! Quedó como estaba antes. Mándele a Claude una captura."; exit 1
fi

echo "==> 3/4 Fotos y archivos que falten"
if [ -n "$ARCH" ]; then
  tar -xzf "$ARCH" -C "$RAIZ/storage/app" --skip-old-files 2>/dev/null
  chown -R "$USR:$USR" "$RAIZ/storage/app"
fi

echo "==> 4/4 Poniendo el sistema a funcionar"
cd "$RAIZ"
run $PHP artisan migrate --force
run $PHP artisan optimize:clear >/dev/null; run $PHP artisan config:cache >/dev/null; run $PHP artisan view:cache >/dev/null
run $PHP artisan up >/dev/null
CODE=$(curl -sk -o /dev/null -w '%{http_code}' --resolve "localproo.com:443:127.0.0.1" "https://localproo.com/login")
echo
echo "LISTO. LocalProo quedó como estaba en $(basename "$BASE"). Sitio: $CODE"
echo "Si se arrepiente, lo de antes está en $ANTES"
