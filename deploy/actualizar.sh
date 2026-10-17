#!/usr/bin/env bash
# Actualiza LocalPRO en el VPS con respaldo y vuelta atrás automática.
# NO toca usuarios ni contraseñas.
# Uso (como root en la Consola web de hPanel):
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/actualizar.sh | bash
# Desarrollado por Jhoset Mejía - DiCreWeb
set -uo pipefail

# Desde la migración el sitio vive en /home/localproo/htdocs/localproo.com (antes en la carpeta de pruebas)
if [ -d /home/localproo/htdocs/localproo.com ]; then CARPETA="localproo.com"; USUARIO="localproo"
else CARPETA="localpro.narinoexposhow.com"; USUARIO="localpro"; fi
DOMINIO="localproo.com"
PHP="php8.2"
RAIZ="/home/$USUARIO/htdocs/$CARPETA"
PRUEBAS=0
# Sitio de pruebas (lo llama deploy/piloto-pruebas.sh): otra carpeta y otro dominio; no instala crons ni toca producción
if [ -n "${LOCALPRO_RAIZ:-}" ]; then
  RAIZ="$LOCALPRO_RAIZ"; USUARIO="$(stat -c %U "$RAIZ")"; DOMINIO="${LOCALPRO_DOMINIO:-pruebas.localproo.com}"; PRUEBAS=1
fi
STAMP="$(date +%Y%m%d-%H%M%S)"
RESP="/home/$USUARIO/respaldos/$STAMP"

[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
cd "$RAIZ" || { echo "No existe $RAIZ"; exit 1; }
run() { sudo -u "$USUARIO" "$@"; }

echo "==> 1/5 Respaldo en $RESP"
mkdir -p "$RESP"
DBN=$(grep ^DB_DATABASE= .env | cut -d= -f2-)
DBU=$(grep ^DB_USERNAME= .env | cut -d= -f2-)
DBP=$(grep ^DB_PASSWORD= .env | cut -d= -f2-)
# Si el disco está casi lleno no se actualiza (un disco lleno tumba el sitio y la base de datos)
USO=$(df --output=pcent / | tail -1 | tr -dc '0-9')
if [ "${USO:-0}" -ge 90 ]; then
  echo "El disco está al ${USO}%. Libero respaldos viejos antes de seguir..."
  ls -1dt /home/$USUARIO/respaldos/*/ 2>/dev/null | grep -v '/diario/$' | tail -n +2 | xargs -r rm -rf
  USO=$(df --output=pcent / | tail -1 | tr -dc '0-9')
  [ "${USO:-0}" -ge 90 ] && { echo "El disco sigue al ${USO}%. No actualizo. Mándele a Claude esta pantalla."; exit 1; }
fi
if ! MYSQL_PWD="$DBP" mysqldump -u "$DBU" --single-transaction --no-tablespaces "$DBN" | gzip > "$RESP/base.sql.gz"; then
  echo "No se pudo respaldar la base de datos. No continúo."; rm -rf "$RESP"; exit 1
fi
cp -a "$RAIZ" "$RESP/codigo"
rm -rf "$RESP/codigo/storage/logs"/*.log "$RESP/codigo/storage/framework/cache/data" 2>/dev/null
chown -R "$USUARIO:$USUARIO" "/home/$USUARIO/respaldos"
echo "    Respaldo listo."
# Guarda solo los 3 respaldos de actualización más recientes. La carpeta "diario" (respaldo de cada noche) NO se toca aquí.
ls -1dt /home/$USUARIO/respaldos/*/ 2>/dev/null | grep -v '/diario/$' | tail -n +4 | xargs -r rm -rf

restaurar() {
  echo
  echo "!! Algo falló. Restaurando la versión anterior..."
  cd / || true
  rm -rf "$RAIZ"
  cp -a "$RESP/codigo" "$RAIZ"
  cd "$RAIZ" || true
  gunzip -c "$RESP/base.sql.gz" | MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN"
  run $PHP "$RAIZ/artisan" optimize:clear >/dev/null 2>&1
  run $PHP "$RAIZ/artisan" config:cache >/dev/null 2>&1
  echo "!! Sistema restaurado como estaba. Mándele a Claude una captura de los errores de arriba."
  exit 1
}

echo "==> 2/5 Código nuevo desde GitHub"
ANTES=$(run git rev-parse HEAD)
run git checkout -- composer.lock 2>/dev/null
# Si alguien editó archivos directo en el servidor, se guardan aparte (no se pierden) antes de traer el código nuevo
if ! run git diff --quiet; then
  CAMBIOS="/home/$USUARIO/respaldos/cambios-servidor-$STAMP.patch"
  run git diff > "$CAMBIOS"
  echo "    Había cambios hechos a mano en el servidor: guardados en $CAMBIOS"
  run git diff --stat
  run git stash push -m "cambios-servidor-$STAMP" >/dev/null
fi
# Archivos nuevos creados a mano en el servidor que ahora vienen en GitHub: se guardan aparte para que no bloqueen
run git fetch -q origin
RAMA=$(run git rev-parse --abbrev-ref HEAD)
for F in $(run git diff --name-only --diff-filter=A HEAD "origin/$RAMA" 2>/dev/null); do
  if [ -e "$F" ] && ! run git ls-files --error-unmatch "$F" >/dev/null 2>&1; then
    mkdir -p "/home/$USUARIO/respaldos/archivos-servidor-$STAMP/$(dirname "$F")"
    mv "$F" "/home/$USUARIO/respaldos/archivos-servidor-$STAMP/$F"
    echo "    Archivo hecho a mano guardado aparte: $F"
  fi
done
# Si no se pudo traer el código, no se tocó nada: no hace falta restaurar
run git pull --ff-only || { echo "!! No se pudo traer el código nuevo. El sistema sigue igual que antes."; exit 1; }

echo "==> 3/5 Dependencias"
# Solo se bajan versiones nuevas de librerías cuando cambia composer.json; si no, se instalan las mismas que ya
# funcionaban (composer.lock). Así una librería nueva no tumba el sitio en una actualización normal.
if [ -f composer.lock ] && run git diff --quiet "$ANTES" HEAD -- composer.json; then
  run $PHP -d memory_limit=-1 /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction || restaurar
else
  echo "    composer.json cambió: actualizando librerías (puede tardar unos minutos)"
  run $PHP -d memory_limit=-1 /usr/local/bin/composer update --no-dev --optimize-autoloader --no-interaction || restaurar
fi

echo "==> 4/5 Base de datos y caché"
run $PHP artisan migrate --force || restaurar
# El sistema funciona solo en localproo.com (los enlaces de asesores, catálogos y correos salen con ese dominio)
if ! grep -q "^APP_URL=https://$DOMINIO" .env; then
  sed -i "s|^APP_URL=.*|APP_URL=https://$DOMINIO|" .env
  echo "    Dominio del sistema: https://$DOMINIO"
fi
run $PHP artisan optimize:clear
run $PHP artisan config:cache || restaurar
run $PHP artisan view:cache || restaurar

# Respaldo diario automático (3:15 a. m.) y correo sin servidor configurado
if [ "$PRUEBAS" = 0 ]; then
CRON=/etc/cron.d/localpro-respaldo
if [ ! -f "$CRON" ]; then
  echo "15 3 * * * root /bin/bash $RAIZ/deploy/respaldo-diario.sh >> /var/log/localpro-respaldo.log 2>&1" > "$CRON"
  chmod 644 "$CRON"
  echo "    Respaldo diario programado (3:15 a. m., se guardan 10 días)."
fi
# Vigilante cada 5 minutos: si el sitio se cae, reinicia PHP/nginx/MySQL solo y cuida el disco
VIG=/etc/cron.d/localpro-vigilante
LINEA="*/5 * * * * root /bin/bash $RAIZ/deploy/vigilante.sh >> /var/log/localpro-vigilante.log 2>&1"
if [ ! -f "$VIG" ] || ! grep -qF "$LINEA" "$VIG"; then
  echo "$LINEA" > "$VIG"; chmod 644 "$VIG"
  echo "    Vigilante instalado (revisa el sitio cada 5 minutos)."
fi
# Piloto automático (si ya se activó una vez): se asegura de que el cron siga apuntando al lugar correcto
if [ -s /etc/localpro-estado-token ]; then
  echo "*/2 * * * * root /bin/bash $RAIZ/deploy/piloto-automatico.sh >> /var/log/localpro-piloto.log 2>&1" > /etc/cron.d/localpro-piloto
  chmod 644 /etc/cron.d/localpro-piloto
fi
# Que los registros de errores no crezcan sin fin
cat > /etc/logrotate.d/localpro <<LR
$RAIZ/storage/logs/*.log /var/log/localpro-*.log {
    weekly
    rotate 4
    compress
    missingok
    notifempty
    copytruncate
    maxsize 100M
}
LR
fi # fin de lo que solo se hace en producción
if grep -q "^MAIL_HOST=mailhog" .env; then
  sed -i "s/^MAIL_MAILER=.*/MAIL_MAILER=log/" .env
  run $PHP artisan config:cache >/dev/null
fi

echo "==> 5/5 Comprobación"
HOSTAPP=$(grep '^APP_URL=' .env | sed 's#.*://##; s#/.*##; s#"##g'); HOSTAPP=${HOSTAPP:-$DOMINIO}
CODE=$(curl -s -o /dev/null -w '%{http_code}' --resolve "$HOSTAPP:443:127.0.0.1" "https://$HOSTAPP/login" -k)
# En el sitio de pruebas la página pide clave (401): eso también es "funciona"
if [ "$CODE" != "200" ] && ! { [ "$PRUEBAS" = 1 ] && [ "$CODE" = "401" ]; }; then echo "La página respondió $CODE"; restaurar; fi

echo
echo "LISTO. LocalPRO actualizado: $(run $PHP artisan --version)"
echo "Respaldo guardado en $RESP"
