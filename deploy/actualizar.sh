#!/usr/bin/env bash
# Actualiza LocalPRO en el VPS con respaldo y vuelta atrás automática.
# NO toca usuarios ni contraseñas.
# Uso (como root en la Consola web de hPanel):
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/actualizar.sh | bash
# Desarrollado por Jhoset Mejía - DiCreWeb
set -uo pipefail

# Nombre de la carpeta en el servidor (quedó con el nombre del dominio de pruebas; es solo interno)
CARPETA="localpro.narinoexposhow.com"
DOMINIO="localproo.com"
USUARIO="localpro"
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
if ! MYSQL_PWD="$DBP" mysqldump -u "$DBU" --single-transaction --no-tablespaces "$DBN" > "$RESP/base.sql"; then
  echo "No se pudo respaldar la base de datos. No continúo."; exit 1
fi
cp -a "$RAIZ" "$RESP/codigo"
chown -R "$USUARIO:$USUARIO" "/home/$USUARIO/respaldos"
echo "    Respaldo listo."

restaurar() {
  echo
  echo "!! Algo falló. Restaurando la versión anterior..."
  rm -rf "$RAIZ"
  cp -a "$RESP/codigo" "$RAIZ"
  MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN" < "$RESP/base.sql"
  run $PHP "$RAIZ/artisan" optimize:clear >/dev/null 2>&1
  run $PHP "$RAIZ/artisan" config:cache >/dev/null 2>&1
  echo "!! Sistema restaurado como estaba. Mándele a Claude una captura de los errores de arriba."
  exit 1
}

echo "==> 2/5 Código nuevo desde GitHub"
run git checkout -- composer.lock 2>/dev/null
run git pull --ff-only || restaurar

echo "==> 3/5 Dependencias (puede tardar unos minutos)"
run $PHP -d memory_limit=-1 /usr/local/bin/composer update --no-dev --optimize-autoloader --no-interaction || restaurar

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
  echo "    Respaldo diario programado (3:15 a. m., se guardan 15 días)."
fi
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
