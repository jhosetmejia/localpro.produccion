#!/usr/bin/env bash
# Instalación de LocalPRO en el VPS Hostinger (Ubuntu 24.04 + CloudPanel)
# Uso (como root en la "Consola web" de hPanel):
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/instalar-vps.sh | bash
# Desarrollado por Jhoset Mejía - DiCreWeb
set -euo pipefail

DOMINIO="localproo.com"
USUARIO="localproo"
PHPV="8.2"
DB="localpro"
REPO="https://github.com/jhosetmejia/localpro.produccion.git"
ADMIN_EMAIL="${ADMIN_EMAIL:-jmmg1994@gmail.com}"
CRED="/root/localpro-credenciales.txt"
RAIZ="/home/$USUARIO/htdocs/$DOMINIO"
PHP="php$PHPV"

gen() { openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 20; }

[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
command -v clpctl >/dev/null || { echo "No se encontró clpctl (CloudPanel)"; exit 1; }

# Reutiliza claves si ya se corrió antes
if [ -f "$CRED" ]; then . "$CRED"; fi
SITE_PASS="${SITE_PASS:-$(gen)}"
DB_PASS="${DB_PASS:-$(gen)}"
ADMIN_PASS="${ADMIN_PASS:-$(gen)}"
umask 077
cat > "$CRED" <<EOF
SITE_PASS=$SITE_PASS
DB_PASS=$DB_PASS
ADMIN_PASS=$ADMIN_PASS
# Acceso al sistema: https://$DOMINIO  ->  usuario $ADMIN_EMAIL  /  clave = ADMIN_PASS
EOF

echo "==> 1/6 Sitio en CloudPanel"
if [ ! -d "/home/$USUARIO" ]; then
  ok=0
  for T in "Laravel 10" "Laravel 11" "Laravel 12"; do
    if clpctl site:add:php --domainName="$DOMINIO" --phpVersion="$PHPV" \
        --vhostTemplate="$T" --siteUser="$USUARIO" --siteUserPassword="$SITE_PASS"; then ok=1; break; fi
  done
  [ $ok = 1 ] || { echo "No se pudo crear el sitio"; exit 1; }
else
  echo "    (el sitio ya existe, sigo)"
fi

echo "==> 2/6 Base de datos"
clpctl db:add --domainName="$DOMINIO" --databaseName="$DB" \
  --databaseUserName="$DB" --databaseUserPassword="$DB_PASS" 2>/dev/null || echo "    (la base ya existe, sigo)"

echo "==> 3/6 Código desde GitHub"
if [ -d "$RAIZ/.git" ]; then
  sudo -u "$USUARIO" git -C "$RAIZ" pull --ff-only
else
  if [ -n "$(ls -A "$RAIZ" 2>/dev/null)" ]; then
    mv "$RAIZ" "$RAIZ.respaldo.$(date +%s)"
  fi
  rm -rf "$RAIZ"
  sudo -u "$USUARIO" git clone "$REPO" "$RAIZ"
fi
chown -R "$USUARIO:$USUARIO" "$RAIZ"

echo "==> 4/6 Dependencias y .env"
cd "$RAIZ"
sudo -u "$USUARIO" $PHP /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction
if [ ! -f .env ]; then
  sudo -u "$USUARIO" cp ".env - copia.example" .env
  sudo -u "$USUARIO" sed -i \
    -e "s|^APP_NAME=.*|APP_NAME=LocalPRO|" \
    -e "s|^APP_ENV=.*|APP_ENV=production|" \
    -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
    -e "s|^APP_URL=.*|APP_URL=https://$DOMINIO|" \
    -e "s|^LOG_LEVEL=.*|LOG_LEVEL=error|" \
    -e "s|^DB_DATABASE=.*|DB_DATABASE=$DB|" \
    -e "s|^DB_USERNAME=.*|DB_USERNAME=$DB|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" \
    -e "s|^ADMIN_EMAIL=.*|ADMIN_EMAIL=$ADMIN_EMAIL|" \
    -e "s|^ADMIN_PASSWORD=.*|ADMIN_PASSWORD=$ADMIN_PASS|" .env
  sudo -u "$USUARIO" $PHP artisan key:generate --force
fi

echo "==> 5/6 Tablas y datos iniciales"
if sudo -u "$USUARIO" $PHP artisan migrate:status 2>/dev/null | grep -q "Ran"; then
  sudo -u "$USUARIO" $PHP artisan migrate --force
else
  sudo -u "$USUARIO" $PHP artisan migrate --seed --force
fi
sudo -u "$USUARIO" $PHP artisan storage:link 2>/dev/null || true
sudo -u "$USUARIO" $PHP artisan optimize:clear
sudo -u "$USUARIO" $PHP artisan config:cache
sudo -u "$USUARIO" $PHP artisan view:cache

echo "==> 6/6 Certificado SSL"
clpctl lets-encrypt:install:certificate --domainName="$DOMINIO" || echo "    (SSL falló; se puede emitir luego desde CloudPanel > SSL/TLS)"

echo
echo "LISTO: https://$DOMINIO"
echo "Usuario: $ADMIN_EMAIL"
echo "Clave:   $(grep '^ADMIN_PASS=' "$CRED" | cut -d= -f2)"
echo "(Guardada en $CRED. Cámbiela al entrar, desde Perfil.)"
