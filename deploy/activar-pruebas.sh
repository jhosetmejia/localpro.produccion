#!/usr/bin/env bash
# Crea el SITIO DE PRUEBAS https://pruebas.localproo.com (se corre UNA vez, como root en la Consola web de hPanel):
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/activar-pruebas.sh | bash
# Antes: en Cloudflare, registro A "pruebas" → 2.25.153.240 con la nube GRIS (solo DNS).
# - Sitio aparte en CloudPanel con su propia base de datos (copia de producción) y la rama "staging" de GitHub.
# - Pide usuario y clave para entrar. No envía correos ni cobra pagos reales (Wompi y Meta apagados).
# - Se actualiza solo cada 2 minutos con lo que llegue a la rama "staging". Producción no se toca.
set -uo pipefail
[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
DOM="pruebas.localproo.com"; SUSR="localpro-pruebas"; RAIZP="/home/$SUSR/htdocs/$DOM"
# Carpeta del sitio de producción (la que atiende localproo.com)
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
PROD="$RAIZ"
PHP="php8.2"; REPO="https://github.com/jhosetmejia/localpro.produccion"
aleatorio() { openssl rand -hex "$1"; }
paso() { echo; echo "==> $*"; }

IP_SERV=$(curl -fsS -4 https://ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')
IP_DNS=$(getent ahostsv4 "$DOM" | awk 'NR==1{print $1}')
if [ -z "$IP_DNS" ]; then
  echo "!! $DOM todavía no existe en el DNS. Créelo en Cloudflare (A · pruebas · $IP_SERV · nube gris) y vuelva a correr esto."; exit 1
fi
[ "$IP_DNS" != "$IP_SERV" ] && echo "   Aviso: $DOM apunta a $IP_DNS y este servidor es $IP_SERV (si tiene la nube naranja, póngala gris)."

paso "1/7 Sitio en CloudPanel"
if [ ! -d "/home/$SUSR" ]; then
  CLAVE_SO="$(aleatorio 12)"
  ok=0
  for T in 'Laravel 12' 'Laravel 11' 'Laravel 10' 'Generic'; do
    if clpctl site:add:php --domainName="$DOM" --phpVersion=8.2 --vhostTemplate="$T" --siteUser="$SUSR" --siteUserPassword="$CLAVE_SO" >/dev/null 2>&1; then ok=1; echo "    Sitio creado (plantilla $T)"; break; fi
  done
  [ $ok = 1 ] || { echo "!! CloudPanel no pudo crear el sitio. Mándele a Claude esta pantalla."; exit 1; }
else
  echo "    Ya existía"
fi

paso "2/7 Base de datos"
DBN="localpro_pruebas"; DBU="localpro_pruebas"
if [ -f /etc/localpro-pruebas.conf ]; then . /etc/localpro-pruebas.conf; else
  DBP="$(aleatorio 16)"
  clpctl db:add --domainName="$DOM" --databaseName="$DBN" --databaseUserName="$DBU" --databaseUserPassword="$DBP" >/dev/null 2>&1 \
    || { echo "!! No se pudo crear la base de datos. Mándele a Claude esta pantalla."; exit 1; }
  CLAVE="$(aleatorio 5)"
  printf 'RAIZP=%q\nDOM=%q\nDBN=%q\nDBU=%q\nDBP=%q\nCLAVE=%q\n' "$RAIZP" "$DOM" "$DBN" "$DBU" "$DBP" "$CLAVE" > /etc/localpro-pruebas.conf
  chmod 600 /etc/localpro-pruebas.conf
fi

paso "3/7 Código (rama staging de GitHub)"
if [ ! -d "$RAIZP/.git" ]; then
  mkdir -p "$RAIZP"; find "$RAIZP" -mindepth 1 -delete
  chown "$SUSR:$SUSR" "$RAIZP"
  sudo -u "$SUSR" git clone -q -b staging "$REPO" "$RAIZP" || { echo "!! No se pudo bajar el código."; exit 1; }
fi

paso "4/7 Configuración (.env de pruebas)"
cp "$PROD/.env" "$RAIZP/.env"
pon() { local k="$1" v="$2"; if grep -q "^$k=" "$RAIZP/.env"; then sed -i "s|^$k=.*|$k=$v|" "$RAIZP/.env"; else echo "$k=$v" >> "$RAIZP/.env"; fi; }
pon APP_ENV staging; pon APP_DEBUG false; pon APP_URL "https://$DOM"; pon APP_DOMINIO "$DOM"; sed -i '/^SESSION_DOMAIN=/d' "$RAIZP/.env"
pon DB_DATABASE "$DBN"; pon DB_USERNAME "$DBU"; pon DB_PASSWORD "$DBP"
pon MAIL_MAILER log; pon CLAVE_PRUEBAS "$CLAVE"
for k in WOMPI_PUBLIC_KEY WOMPI_PRIVATE_KEY WOMPI_INTEGRITY_SECRET WOMPI_EVENTS_SECRET META_APP_ID META_APP_SECRET META_CONFIG_ID; do pon "$k" ""; done
chown "$SUSR:$SUSR" "$RAIZP/.env"; chmod 640 "$RAIZP/.env"

paso "5/7 Copia de la base y los archivos de producción"
bash "$PROD/deploy/refrescar-pruebas.sh" --sin-migrar || exit 1

paso "6/7 Librerías y caché"
cd "$RAIZP"
sudo -u "$SUSR" $PHP -d memory_limit=-1 /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction -q || { echo "!! Falló composer."; exit 1; }
sudo -u "$SUSR" $PHP artisan storage:link >/dev/null 2>&1
sudo -u "$SUSR" $PHP artisan migrate --force
sudo -u "$SUSR" $PHP artisan optimize:clear >/dev/null; sudo -u "$SUSR" $PHP artisan config:cache >/dev/null; sudo -u "$SUSR" $PHP artisan view:cache >/dev/null

paso "7/7 Certificado SSL y actualización automática"
clpctl lets-encrypt:install:certificate --domainName="$DOM" >/dev/null 2>&1 && echo "    SSL listo" || echo "    Aviso: no se pudo poner el SSL ahora (revise que la nube esté gris). El resto quedó listo."
echo "*/2 * * * * root /bin/bash $PROD/deploy/piloto-pruebas.sh >> /var/log/localpro-pruebas.log 2>&1" > /etc/cron.d/localpro-pruebas
chmod 644 /etc/cron.d/localpro-pruebas
echo "$(date '+%F %T') creado · $(sudo -u "$SUSR" git -C "$RAIZP" log -1 --format='%h')" > /var/lib/localpro/pruebas-estado

echo
echo "=================================================================="
echo " SITIO DE PRUEBAS LISTO:  https://$DOM"
echo "   Usuario del navegador: localpro"
echo "   Clave del navegador:   $CLAVE"
echo " Adentro entra con su mismo correo y clave de LocalPro."
echo " Es una COPIA: lo que haga ahí no afecta el sistema real."
echo " No envía correos ni cobra pagos reales."
echo "=================================================================="
