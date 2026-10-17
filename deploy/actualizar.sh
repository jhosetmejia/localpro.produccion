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
  rm -rf "$RAIZ"
  cp -a "$RESP/codigo" "$RAIZ"
  gunzip -c "$RESP/base.sql.gz" | MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN"
  run $PHP "$RAIZ/artisan" optimize:clear >/dev/null 2>&1
  run $PHP "$RAIZ/artisan" config:cache >/dev/null 2>&1
  echo "!! Sistema restaurado como estaba. Mándele a Claude una captura de los errores de arriba."
  exit 1
}

echo "==> 2/5 Código nuevo desde GitHub"
ANTES=$(run git rev-parse HEAD)
run git checkout -- composer.lock 2>/dev/null
run git pull --ff-only || restaurar

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
if grep -q "^MAIL_HOST=mailhog" .env; then
  sed -i "s/^MAIL_MAILER=.*/MAIL_MAILER=log/" .env
  run $PHP artisan config:cache >/dev/null
fi

echo "==> 5/5 Comprobación"
HOSTAPP=$(grep '^APP_URL=' .env | sed 's#.*://##; s#/.*##; s#"##g'); HOSTAPP=${HOSTAPP:-$DOMINIO}
CODE=$(curl -s -o /dev/null -w '%{http_code}' --resolve "$HOSTAPP:443:127.0.0.1" "https://$HOSTAPP/login" -k)
[ "$CODE" = "200" ] || { echo "La página respondió $CODE"; restaurar; }

echo
echo "LISTO. LocalPRO actualizado: $(run $PHP artisan --version)"
echo "Respaldo guardado en $RESP"
