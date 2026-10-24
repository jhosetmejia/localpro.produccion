#!/usr/bin/env bash
# Pasa LocalPro de la carpeta vieja del VPS a un sitio propio "localproo.com" en CloudPanel (/home/localproo/htdocs/localproo.com),
# con su carpeta, su base de datos y su certificado SSL. Al final el subdominio viejo ya no se necesita.
# Si algo falla, deja el sistema como estaba.
# Uso (como root):
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/migrar-a-localproo.sh | bash
# Desarrollado por Jhoset Mejía - DiCreWeb
set -uo pipefail

RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
VIEJO="$(basename "$RAIZ")"
NUEVO="localproo.com"
U_VIEJO="localpro"
U_NUEVO="localproo"
DB_NUEVA="localproo"
PHPV="8.2"
PHP="php$PHPV"
IP="2.25.153.240"
R_VIEJO="/home/$U_VIEJO/htdocs/$VIEJO"
R_NUEVO="/home/$U_NUEVO/htdocs/$NUEVO"
STAMP="$(date +%Y%m%d-%H%M%S)"
RESP="/root/migracion-localproo-$STAMP"
CRED="/root/localproo-credenciales.txt"

gen() { openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 20; }
[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
command -v clpctl >/dev/null || { echo "No se encontró clpctl (CloudPanel)"; exit 1; }
[ -d "$R_VIEJO" ] || { echo "No existe $R_VIEJO (¿ya se migró?)"; exit 1; }
[ -d "/home/$U_NUEVO" ] && { echo "El sitio $NUEVO ya existe en CloudPanel. No continúo."; exit 1; }

VHOST_V="/etc/nginx/sites-enabled/$(ls /etc/nginx/sites-enabled/ | grep -F "$VIEJO" | head -1)"
[ -f "$VHOST_V" ] || { echo "No encontré la configuración de nginx de $VIEJO"; exit 1; }

echo "==> 1/8 Revisando que $NUEVO apunte a este servidor"
command -v dig >/dev/null || apt-get install -y -qq dnsutils >/dev/null 2>&1
A=$(dig +short A "$NUEVO" @1.1.1.1 | tail -1)
echo "    $NUEVO -> ${A:-nada}"
[ -n "$A" ] || { echo "    $NUEVO no apunta a ningún lado. No continúo."; exit 1; }

echo "==> 2/8 Respaldo completo en $RESP (y sistema en mantenimiento unos minutos)"
mkdir -p "$RESP"; chmod 700 "$RESP"
cp "$VHOST_V" "$RESP/vhost-viejo.conf"
crontab -u "$U_VIEJO" -l > "$RESP/crontab-viejo.txt" 2>/dev/null || true
DBN=$(grep ^DB_DATABASE= "$R_VIEJO/.env" | cut -d= -f2-)
DBU=$(grep ^DB_USERNAME= "$R_VIEJO/.env" | cut -d= -f2-)
DBP=$(grep ^DB_PASSWORD= "$R_VIEJO/.env" | cut -d= -f2-)
sudo -u "$U_VIEJO" $PHP "$R_VIEJO/artisan" down --retry=60 >/dev/null 2>&1

volver() {
  echo
  echo "!! Algo falló. Dejando todo como estaba..."
  cp "$RESP/vhost-viejo.conf" "$VHOST_V"
  nginx -t >/dev/null 2>&1 && systemctl reload nginx
  sudo -u "$U_VIEJO" $PHP "$R_VIEJO/artisan" up >/dev/null 2>&1
  echo "!! El sistema sigue funcionando como antes en https://$NUEVO."
  echo "!! (El sitio nuevo a medio hacer quedó en CloudPanel; no estorba.) Mándele a Claude una captura de los errores de arriba."
  exit 1
}

MYSQL_PWD="$DBP" mysqldump -u "$DBU" --single-transaction --no-tablespaces "$DBN" > "$RESP/base.sql" || volver
echo "    Base respaldada ($(du -h "$RESP/base.sql" | cut -f1))."

echo "==> 3/8 Creando el sitio $NUEVO en CloudPanel"
SITE_PASS=$(gen); DB_PASS=$(gen)
umask 077
printf 'SITE_PASS=%s\nDB_PASS=%s\n# Sitio %s (usuario %s), base %s\n' "$SITE_PASS" "$DB_PASS" "$NUEVO" "$U_NUEVO" "$DB_NUEVA" > "$CRED"
umask 022
ok=0
for T in "Laravel 12" "Laravel 11" "Laravel 10"; do
  if clpctl site:add:php --domainName="$NUEVO" --phpVersion="$PHPV" --vhostTemplate="$T" \
      --siteUser="$U_NUEVO" --siteUserPassword="$SITE_PASS" >/dev/null 2>&1; then ok=1; echo "    Plantilla: $T"; break; fi
done
[ $ok = 1 ] || volver
clpctl db:add --domainName="$NUEVO" --databaseName="$DB_NUEVA" --databaseUserName="$DB_NUEVA" --databaseUserPassword="$DB_PASS" >/dev/null || volver
MYSQL_PWD="$DB_PASS" mysql -u "$DB_NUEVA" "$DB_NUEVA" < "$RESP/base.sql" || volver
echo "    Sitio y base listos (credenciales en $CRED)."

echo "==> 4/8 Copiando el sistema a $R_NUEVO"
rm -rf "$R_NUEVO"
cp -a "$R_VIEJO" "$R_NUEVO" || volver
rm -f "$R_NUEVO/public/storage" "$R_NUEVO/storage/framework/down"
chown -R "$U_NUEVO:$U_NUEVO" "$R_NUEVO"
sed -i -e "s|^DB_DATABASE=.*|DB_DATABASE=$DB_NUEVA|" -e "s|^DB_USERNAME=.*|DB_USERNAME=$DB_NUEVA|" \
       -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" -e "s|^APP_URL=.*|APP_URL=https://$NUEVO|" "$R_NUEVO/.env"
cd "$R_NUEVO"
sudo -u "$U_NUEVO" git config --global --add safe.directory "$R_NUEVO" 2>/dev/null
sudo -u "$U_NUEVO" $PHP artisan storage:link >/dev/null 2>&1
sudo -u "$U_NUEVO" $PHP artisan optimize:clear >/dev/null || volver
sudo -u "$U_NUEVO" $PHP artisan config:cache >/dev/null || volver
sudo -u "$U_NUEVO" $PHP artisan view:cache >/dev/null || volver
sudo -u "$U_NUEVO" git pull --ff-only origin master >/dev/null 2>&1 || true

echo "==> 5/8 Pasando $NUEVO al sitio nuevo"
sed -i -E "s/ (www\.)?$NUEVO//g" "$VHOST_V"
nginx -t >/dev/null 2>&1 && systemctl reload nginx || volver

echo "==> 6/8 Certificado SSL para $NUEVO y www.$NUEVO"
if ! clpctl lets-encrypt:install:certificate --domainName="$NUEVO" --subjectAlternativeName="www.$NUEVO" 2>&1 | tail -2; then
  echo "    (Aviso) No salió el certificado con www; intento solo $NUEVO"
  clpctl lets-encrypt:install:certificate --domainName="$NUEVO" 2>&1 | tail -2
fi

echo "==> 7/8 Prueba"
# -k: si Let's Encrypt aún no salió, CloudPanel deja un certificado propio (Cloudflare en "Full" lo acepta igual)
CODE=$(curl -sk -o /dev/null -w '%{http_code}' --resolve "$NUEVO:443:127.0.0.1" "https://$NUEVO/login")
echo "    https://$NUEVO/login -> $CODE"
[ "$CODE" = "200" ] || volver
CERT=$(echo | openssl s_client -servername "$NUEVO" -connect 127.0.0.1:443 2>/dev/null | openssl x509 -noout -issuer 2>/dev/null)
echo "    Certificado: ${CERT:-desconocido}"

echo "==> 8/8 Tareas automáticas (respaldo diario y publicaciones programadas)"
echo "15 3 * * * root /bin/bash $R_NUEVO/deploy/respaldo-diario.sh >> /var/log/localpro-respaldo.log 2>&1" > /etc/cron.d/localpro-respaldo
chmod 644 /etc/cron.d/localpro-respaldo
if [ -s "$RESP/crontab-viejo.txt" ]; then
  sed -e "s#$R_VIEJO#$R_NUEVO#g" "$RESP/crontab-viejo.txt" | crontab -u "$U_NUEVO" -
  crontab -u "$U_VIEJO" -r 2>/dev/null || true
else
  echo "* * * * * cd $R_NUEVO && $PHP artisan schedule:run >> /dev/null 2>&1" | crontab -u "$U_NUEVO" -
fi
# Tareas de /etc/cron.d (piloto automático, vigilante, respaldos, pruebas) apuntan a la carpeta nueva
for c in /etc/cron.d/localpro-*; do [ -f "$c" ] && sed -i "s#$R_VIEJO#$R_NUEVO#g" "$c"; done
# Comando corto "localproo"
install -m 755 "$R_NUEVO/deploy/localproo-comando.sh" /usr/local/bin/localproo 2>/dev/null
mkdir -p "/home/$U_NUEVO/respaldos" && cp -a "/home/$U_VIEJO/respaldos/." "/home/$U_NUEVO/respaldos/" 2>/dev/null
chown -R "$U_NUEVO:$U_NUEVO" "/home/$U_NUEVO/respaldos"

echo
echo "LISTO. LocalPro funciona en https://$NUEVO con su propio sitio, carpeta y base de datos."
echo "Carpeta nueva: $R_NUEVO   (usuario del sitio: $U_NUEVO)"
echo "Respaldo de todo lo anterior: $RESP"
OTROS=$(grep -rls "$VIEJO" /home/*/htdocs/*/.env 2>/dev/null | grep -v "^$R_VIEJO" | grep -v "^$R_NUEVO")
[ -n "$OTROS" ] && echo "OJO: estos otros sistemas todavía usan $VIEJO (cámbielo por $NUEVO): $OTROS"
echo
echo "Revise https://$NUEVO y, si todo está bien, borre el sitio viejo con:"
echo "  curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/borrar-sitio-viejo.sh | bash"
