#!/usr/bin/env bash
# Activa el piloto automático de LocalPro (se corre UNA sola vez, como root en la Consola web de hPanel):
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/activar-piloto.sh | bash
# Desde ahí el servidor se actualiza solo cada vez que hay cambios en GitHub, ejecuta las órdenes de
# deploy/orden.txt y publica un reporte de estado en una dirección secreta. No toca usuarios ni contraseñas.
set -uo pipefail
[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
RAIZ="$( [ -d /home/localproo/htdocs/localproo.com ] && echo /home/localproo/htdocs/localproo.com || echo /home/localpro/htdocs/localpro.narinoexposhow.com )"
USR="$(stat -c %U "$RAIZ")"
cd "$RAIZ" || { echo "No existe $RAIZ"; exit 1; }

echo "==> Trayendo la última versión (con respaldo)"
curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/actualizar.sh | bash || {
  echo "La actualización falló y el sistema quedó como estaba. Mándele a Claude esta pantalla."; exit 1; }

echo "==> Instalando el piloto automático (cada 2 minutos)"
[ -s /etc/localpro-estado-token ] || { openssl rand -hex 16 > /etc/localpro-estado-token; chmod 600 /etc/localpro-estado-token; }
echo "*/2 * * * * root /bin/bash $RAIZ/deploy/piloto-automatico.sh >> /var/log/localpro-piloto.log 2>&1" > /etc/cron.d/localpro-piloto
chmod 644 /etc/cron.d/localpro-piloto
# Lo que ya se ejecutó de orden.txt antes de activar no se vuelve a ejecutar
mkdir -p /var/lib/localpro
grep -v '^\s*#' deploy/orden.txt 2>/dev/null | grep '|' | tail -1 | cut -d'|' -f1 > /var/lib/localpro/ultima-orden
bash "$RAIZ/deploy/piloto-automatico.sh"

echo
echo "=============================================================="
echo " PILOTO AUTOMÁTICO ACTIVO"
echo " Copie esta dirección y mándesela a Claude en el chat:"
echo
echo "   https://localproo.com/estado-$(cat /etc/localpro-estado-token).txt"
echo
echo " (Es el reporte del servidor; no tiene claves ni datos de clientes.)"
echo "=============================================================="
