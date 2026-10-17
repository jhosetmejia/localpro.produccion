#!/usr/bin/env bash
# Vuelve a copiar la base de datos y los archivos de PRODUCCIÓN al sitio de pruebas (pruebas no afecta producción).
# Lo usa activar-pruebas.sh y la orden "refrescar-pruebas" de deploy/orden.txt.
set -uo pipefail
. /etc/localpro-pruebas.conf || { echo "No existe el sitio de pruebas"; exit 1; }
PROD="$( [ -d /home/localproo/htdocs/localproo.com ] && echo /home/localproo/htdocs/localproo.com || echo /home/localpro/htdocs/localpro.narinoexposhow.com )"
SUSR="$(stat -c %U "$RAIZP")"
PN=$(grep ^DB_DATABASE= "$PROD/.env" | cut -d= -f2-); PU=$(grep ^DB_USERNAME= "$PROD/.env" | cut -d= -f2-); PP=$(grep ^DB_PASSWORD= "$PROD/.env" | cut -d= -f2-)
echo "    Copiando la base de datos…"
if ! MYSQL_PWD="$PP" mysqldump -u "$PU" --single-transaction --no-tablespaces "$PN" | MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN"; then
  echo "!! No se pudo copiar la base de datos."; exit 1
fi
# En pruebas no se manda nada a la DIAN: si producción tiene proveedor conectado, aquí queda en modo práctica
MYSQL_PWD="$DBP" mysql -u "$DBU" "$DBN" -e "UPDATE ajustes SET valor='simulado' WHERE clave='fe_proveedor' AND valor='api'" 2>/dev/null
echo "    Copiando fotos y archivos…"
mkdir -p "$RAIZP/storage/app"
for d in public firmas; do [ -d "$PROD/storage/app/$d" ] && rsync -a --delete "$PROD/storage/app/$d/" "$RAIZP/storage/app/$d/"; done
chown -R "$SUSR:$SUSR" "$RAIZP/storage"
if [ "${1:-}" != "--sin-migrar" ]; then
  cd "$RAIZP" && sudo -u "$SUSR" php8.2 artisan migrate --force && sudo -u "$SUSR" php8.2 artisan optimize:clear >/dev/null && sudo -u "$SUSR" php8.2 artisan config:cache >/dev/null
fi
echo "$(date '+%F %T') datos copiados de producción · $(sudo -u "$SUSR" git -C "$RAIZP" log -1 --format='%h')" > /var/lib/localpro/pruebas-estado
echo "    Listo."
