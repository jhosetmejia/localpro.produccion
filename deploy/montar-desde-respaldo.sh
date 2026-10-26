#!/usr/bin/env bash
# PLAN DE EMERGENCIA: monta LocalProo completo en un VPS NUEVO (Ubuntu + CloudPanel) desde la copia de Backblaze.
# Úselo si el servidor actual se perdió. Se corre como root en la Consola web del VPS nuevo:
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/montar-desde-respaldo.sh -o /root/montar.sh && bash /root/montar.sh
# Le pide aquí mismo las llaves de Backblaze y la LLAVE DE CIFRADO de las copias (la que usted guardó).
# Al final: cambie en Cloudflare el registro A de localproo.com (y www) a la IP de este VPS.
set -uo pipefail
[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
command -v clpctl >/dev/null || { echo "Este VPS no tiene CloudPanel. En Hostinger elija el sistema operativo 'Ubuntu 24.04 con CloudPanel'."; exit 1; }
RAW="https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy"
RAIZ="/home/localproo/htdocs/localproo.com"; USR="localproo"; PHP="php8.2"; run() { sudo -u "$USR" "$@"; }

echo "==> 1/7 Datos de Backblaze y llave de las copias"
command -v rclone >/dev/null || curl -fsSL https://rclone.org/install.sh | bash >/dev/null || { echo "No se pudo instalar rclone."; exit 1; }
read -r -p "  keyID de Backblaze: " KID < /dev/tty
read -r -s -p "  applicationKey (no se ve al pegar): " AKEY < /dev/tty; echo
read -r -p "  Nombre del bucket: " BUCKET < /dev/tty
read -r -s -p "  Llave de cifrado de las copias (no se ve al pegar): " LLAVE < /dev/tty; echo
[ -n "$KID" ] && [ -n "$AKEY" ] && [ -n "$BUCKET" ] && [ -n "$LLAVE" ] || { echo "Faltan datos."; exit 1; }
rclone config create respaldo b2 account "$KID" key "$AKEY" hard_delete true >/dev/null || { echo "Backblaze no aceptó las llaves."; exit 1; }
chmod 600 /root/.config/rclone/rclone.conf 2>/dev/null
echo "respaldo:$BUCKET/localpro" > /etc/localpro-respaldo.destino
( umask 077; printf '%s' "$LLAVE" > /etc/localpro-respaldo.key )
DESTINO="respaldo:$BUCKET/localpro"

echo "==> 2/7 Bajando la copia más reciente"
DIAS=$(rclone lsf --dirs-only "$DESTINO" 2>/dev/null | tr -d '/' | grep -E '^[0-9]{4}-' | sort -r)
[ -n "$DIAS" ] || { echo "No hay copias en ese bucket."; exit 1; }
clave() { local n; n=$(basename "$1" .sql.gz); n=${n#base-}; local h=${n:9:4}; echo "${n:0:8}${h:-0315}"; }
OUT=/root/restaurar; mkdir -p "$OUT"
BASE=""; ARCH=""; CONF=""
# La base más nueva; las fotos y la configuración del día más reciente que las tenga
for D in $DIAS; do
  mkdir -p "$OUT/$D"; rclone copy "$DESTINO/$D/" "$OUT/$D/" >/dev/null 2>&1
  for f in "$OUT/$D"/*.enc; do [ -f "$f" ] || continue
    openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass file:/etc/localpro-respaldo.key -in "$f" -out "${f%.enc}" 2>/dev/null \
      || { echo "!! La llave de cifrado no abre las copias. Revise que sea la correcta."; exit 1; }
    rm -f "$f"; done
  [ -z "$BASE" ] && BASE=$(for f in "$OUT/$D"/base-*.sql.gz; do [ -f "$f" ] && echo "$(clave "$f") $f"; done | sort | tail -1 | cut -d' ' -f2-)
  [ -z "$ARCH" ] && ARCH=$(ls -1 "$OUT/$D"/archivos-*.tar.gz 2>/dev/null | head -1)
  [ -z "$CONF" ] && CONF=$(ls -1 "$OUT/$D"/config-*.tar.gz 2>/dev/null | head -1)
  [ -n "$BASE" ] && [ -n "$ARCH" ] && [ -n "$CONF" ] && break
done
[ -n "$BASE" ] && gunzip -t "$BASE" || { echo "No encontré una copia de la base que sirva."; exit 1; }
echo "    Base: $(basename "$BASE")  ·  Fotos: $(basename "${ARCH:-ninguna}")  ·  Configuración: $(basename "${CONF:-ninguna}")"

echo "==> 3/7 Instalando el sistema (sitio, base vacía, código, librerías)"
curl -fsSL "$RAW/instalar-vps.sh" | bash || { echo "Falló la instalación. Mándele a Claude una captura."; exit 1; }

echo "==> 4/7 Configuración (.env) de la copia"
if [ -n "$CONF" ]; then
  NDB=$(grep ^DB_DATABASE= "$RAIZ/.env" | cut -d= -f2-); NDU=$(grep ^DB_USERNAME= "$RAIZ/.env" | cut -d= -f2-); NDP=$(grep ^DB_PASSWORD= "$RAIZ/.env" | cut -d= -f2-)
  cp "$RAIZ/.env" "/root/env-instalacion-nueva.bak"
  tar -xzf "$CONF" -C "$RAIZ" .env
  # La base de este VPS tiene su propio usuario y clave
  sed -i -e "s|^DB_DATABASE=.*|DB_DATABASE=$NDB|" -e "s|^DB_USERNAME=.*|DB_USERNAME=$NDU|" -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$NDP|" -e "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" "$RAIZ/.env"
  chown "$USR:$USR" "$RAIZ/.env"; chmod 640 "$RAIZ/.env"
else
  echo "    !! La copia no trae la configuración: habrá que volver a poner las claves de Wompi, Meta y DIAN en el .env."
fi

echo "==> 5/7 Base de datos y fotos"
DBN=$(grep ^DB_DATABASE= "$RAIZ/.env" | cut -d= -f2-); DBU=$(grep ^DB_USERNAME= "$RAIZ/.env" | cut -d= -f2-); DBP=$(grep ^DB_PASSWORD= "$RAIZ/.env" | cut -d= -f2-)
TABLAS=$(MYSQL_PWD="$DBP" mysql -u "$DBU" -N -e "SELECT GROUP_CONCAT(CONCAT('\`',table_name,'\`')) FROM information_schema.tables WHERE table_schema='$DBN'")
[ -n "$TABLAS" ] && [ "$TABLAS" != "NULL" ] && MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN" -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE $TABLAS; SET FOREIGN_KEY_CHECKS=1;"
gunzip -c "$BASE" | MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN" || { echo "Falló al cargar la base. Mándele a Claude una captura."; exit 1; }
[ -n "$ARCH" ] && tar -xzf "$ARCH" -C "$RAIZ/storage/app" && chown -R "$USR:$USR" "$RAIZ/storage/app"

echo "==> 6/7 Poniendo el sistema a funcionar"
cd "$RAIZ"
rm -f public/storage; run $PHP artisan storage:link >/dev/null 2>&1
run $PHP artisan migrate --force
run $PHP artisan optimize:clear >/dev/null; run $PHP artisan config:cache >/dev/null; run $PHP artisan view:cache >/dev/null

echo "==> 7/7 Tareas automáticas (respaldos, vigilante, piloto automático)"
bash "$RAIZ/deploy/actualizar.sh" >/dev/null 2>&1 || true
curl -fsSL "$RAW/activar-piloto.sh" | bash >/dev/null 2>&1 || true
bash "$RAIZ/deploy/respaldo-diario.sh" >> /var/log/localpro-respaldo.log 2>&1 || true
CODE=$(curl -sk -o /dev/null -w '%{http_code}' --resolve "localproo.com:443:127.0.0.1" "https://localproo.com/login")

IP=$(curl -fsS4 https://api.ipify.org 2>/dev/null || hostname -I | awk '{print $1}')
echo
echo "=================================================================="
echo " LISTO. LocalProo quedó montado en este VPS con la copia $(basename "$BASE")."
echo " Prueba interna del sitio: $CODE"
echo
echo " FALTA UN PASO: en Cloudflare → localproo.com → DNS, cambie los"
echo " registros A de localproo.com y www a esta IP:  $IP"
echo " (y los de pruebas.localproo.com si los usa)."
echo
echo " Los usuarios y claves de todos siguen iguales (vienen en la copia)."
echo "=================================================================="
