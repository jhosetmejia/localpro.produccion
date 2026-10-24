#!/usr/bin/env bash
# Pone LocalPro a funcionar en un dominio nuevo (y su www), con certificado SSL, sin dejar de funcionar el anterior.
# Uso: bash dominio-nuevo.sh localproo.com
set -u
NUEVO="$(echo "${1:-}" | tr 'A-Z' 'a-z' | sed 's#^https\?://##; s#/.*##; s#^www\.##')"
[ -z "$NUEVO" ] && { echo "Escriba el dominio: bash dominio-nuevo.sh localproo.com"; exit 1; }
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
VIEJO="$(basename "$RAIZ")"
IP="2.25.153.240"
VHOST=$(ls /etc/nginx/sites-enabled/ 2>/dev/null | grep -F "$VIEJO" | head -1)
VHOST="/etc/nginx/sites-enabled/$VHOST"
PHP="php8.2"

echo "==> 1/5 Revisando el DNS de $NUEVO"
command -v dig >/dev/null || apt-get install -y -qq dnsutils >/dev/null 2>&1
for H in "$NUEVO" "www.$NUEVO"; do
  R=$(dig +short A "$H" @1.1.1.1 | tail -1)
  if [ -z "$R" ]; then echo "    $H: todavía no apunta a ningún lado"; FALTA=1
  elif [ "$R" = "$IP" ]; then echo "    $H -> $R (este servidor) OK"
  else echo "    $H -> $R (Cloudflare u otro; si es Cloudflare está bien)"; fi
done

echo "==> 2/5 Agregando $NUEVO al servidor web"
[ -f "$VHOST" ] || { echo "    No encontré la configuración de nginx de $VIEJO"; exit 1; }
cp "$VHOST" "/root/vhost-respaldo-$(date +%s).conf"
if ! grep -q "$NUEVO" "$VHOST"; then
  sed -i -E "s/(server_name[^;]*$VIEJO)([^;]*);/\1 $NUEVO www.$NUEVO\2;/" "$VHOST"
fi
grep -m2 "server_name" "$VHOST" | sed 's/^/    /'
nginx -t >/dev/null 2>&1 && systemctl reload nginx && echo "    nginx OK" || { echo "    Error en nginx, se restaura"; cp "$(ls -t /root/vhost-respaldo-*.conf | head -1)" "$VHOST"; systemctl reload nginx; exit 1; }

echo "==> 3/5 Certificado SSL (Let's Encrypt) para $NUEVO y www.$NUEVO"
if clpctl lets-encrypt:install:certificate --domainName="$VIEJO" --subjectAlternativeName="$NUEVO,www.$NUEVO" 2>&1 | tail -3; then :; fi
# CloudPanel puede reescribir el server_name al instalar el certificado: se asegura otra vez
grep -q "$NUEVO" "$VHOST" || { sed -i -E "s/(server_name[^;]*$VIEJO)([^;]*);/\1 $NUEVO www.$NUEVO\2;/" "$VHOST"; nginx -t >/dev/null 2>&1 && systemctl reload nginx; }

echo "==> 4/5 Prueba"
C=$(curl -s -o /dev/null -w '%{http_code}' --resolve "$NUEVO:443:127.0.0.1" "https://$NUEVO/login")
echo "    https://$NUEVO/login -> $C"

if [ "$C" = "200" ]; then
  echo "==> 5/5 El sistema usa ahora https://$NUEVO en enlaces, facturas y catálogo"
  sed -i "s|^APP_URL=.*|APP_URL=https://$NUEVO|" "$RAIZ/.env"
  echo "LISTO. LocalPro funciona en https://$NUEVO (y sigue funcionando en https://$VIEJO)"
else
  echo "==> 5/5 Todavía no: el sistema sigue usando https://$VIEJO"
  sed -i "s|^APP_URL=.*|APP_URL=https://$VIEJO|" "$RAIZ/.env"
  echo "El dominio $NUEVO aún no apunta a este servidor ($IP). Cambie su DNS y vuelva a correr este comando."
fi
cd "$RAIZ" && sudo -u localpro $PHP artisan config:cache >/dev/null && sudo -u localpro $PHP artisan view:clear >/dev/null
