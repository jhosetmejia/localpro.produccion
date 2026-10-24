#!/usr/bin/env bash
# Después de migrar a /home/localproo/htdocs/localproo.com: borra el sitio viejo de CloudPanel (su carpeta y su usuario).
# Antes deja una copia comprimida de la carpeta y de su base de datos en /root (por si acaso). La base vieja se borra aparte.
# Uso (como root):
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/borrar-sitio-viejo.sh | bash
set -uo pipefail
NUEVA="/home/localproo/htdocs/localproo.com"
[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
command -v clpctl >/dev/null || { echo "No se encontró clpctl (CloudPanel)"; exit 1; }
[ -f "$NUEVA/artisan" ] || { echo "Primero hay que migrar: no existe $NUEVA"; exit 1; }
CODE=$(curl -sk -o /dev/null -w '%{http_code}' --resolve "localproo.com:443:127.0.0.1" "https://localproo.com/login")
[ "$CODE" = "200" ] || { echo "localproo.com no responde bien desde la carpeta nueva ($CODE). No borro nada."; exit 1; }

VIEJA=""
for d in /home/*/htdocs/*/; do
  d="${d%/}"
  [ "$d" = "$NUEVA" ] && continue
  case "$d" in */pruebas*) continue ;; esac
  [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { VIEJA="$d"; break; }
done
[ -n "$VIEJA" ] || { echo "No hay sitio viejo para borrar. Todo limpio."; exit 0; }
DOM="$(basename "$VIEJA")"
U="$(stat -c %U "$VIEJA")"
STAMP="$(date +%Y%m%d-%H%M%S)"
COPIA="/root/sitio-viejo-$STAMP"
mkdir -p "$COPIA"; chmod 700 "$COPIA"
echo "==> Copia de seguridad de la carpeta vieja y su base en $COPIA"
DBN=$(grep ^DB_DATABASE= "$VIEJA/.env" | cut -d= -f2-); DBU=$(grep ^DB_USERNAME= "$VIEJA/.env" | cut -d= -f2-); DBP=$(grep ^DB_PASSWORD= "$VIEJA/.env" | cut -d= -f2-)
MYSQL_PWD="$DBP" mysqldump -u "$DBU" --single-transaction --no-tablespaces "$DBN" 2>/dev/null | gzip > "$COPIA/base-vieja.sql.gz"
tar -czf "$COPIA/carpeta-vieja.tar.gz" --exclude="$VIEJA/vendor" --exclude="$VIEJA/node_modules" -C "$(dirname "$VIEJA")" "$(basename "$VIEJA")" 2>/dev/null
crontab -u "$U" -r 2>/dev/null || true
for c in /etc/cron.d/localpro-*; do [ -f "$c" ] && grep -q "$VIEJA" "$c" && sed -i "s#$VIEJA#$NUEVA#g" "$c"; done

echo "==> Borrando el sitio viejo de CloudPanel"
if clpctl site:delete --domainName="$DOM" --force >/dev/null 2>&1; then
  echo "    Sitio viejo borrado."
else
  echo "    No se pudo borrar con clpctl. Bórrelo en CloudPanel → Sites (el que no es localproo.com)."
fi
nginx -t >/dev/null 2>&1 && systemctl reload nginx
CODE=$(curl -sk -o /dev/null -w '%{http_code}' --resolve "localproo.com:443:127.0.0.1" "https://localproo.com/login")
echo "==> localproo.com -> $CODE"
echo
echo "LISTO. Queda solo /home/localproo/htdocs/localproo.com (base de datos: localproo)."
echo "Copia de la carpeta y la base viejas en $COPIA (bórrela en unas semanas si todo sigue bien)."
