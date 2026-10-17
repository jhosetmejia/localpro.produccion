#!/usr/bin/env bash
# Piloto del sitio de pruebas: cada 2 minutos, si hay código nuevo en la rama "staging", actualiza pruebas.localproo.com
# (con respaldo y vuelta atrás, igual que producción). Si una versión falla no la reintenta hasta que llegue otra.
set -uo pipefail
exec 8>/var/lock/localpro-pruebas.lock; flock -n 8 || exit 0
. /etc/localpro-pruebas.conf 2>/dev/null || exit 0
SUSR="$(stat -c %U "$RAIZP")"; EST=/var/lib/localpro; mkdir -p "$EST"
cd "$RAIZP" || exit 1
sudo -u "$SUSR" git fetch -q origin staging 2>/dev/null
LOCAL=$(sudo -u "$SUSR" git rev-parse HEAD); REMOTO=$(sudo -u "$SUSR" git rev-parse origin/staging 2>/dev/null)
FALLIDO=$(cat "$EST/pruebas-fallido" 2>/dev/null || true)
[ -n "$REMOTO" ] && [ "$LOCAL" != "$REMOTO" ] && [ "$REMOTO" != "$FALLIDO" ] || exit 0
TMP=$(mktemp); sudo -u "$SUSR" git show origin/staging:deploy/actualizar.sh > "$TMP"
echo "===== $(date '+%F %T') Pruebas → ${REMOTO:0:7} ====="
if LOCALPRO_RAIZ="$RAIZP" LOCALPRO_DOMINIO="$DOM" bash "$TMP"; then
  rm -f "$EST/pruebas-fallido"; echo "$(date '+%F %T') OK · versión ${REMOTO:0:7}" > "$EST/pruebas-estado"
else
  echo "$REMOTO" > "$EST/pruebas-fallido"; echo "$(date '+%F %T') FALLÓ la versión ${REMOTO:0:7} (quedó la anterior)" > "$EST/pruebas-estado"
fi
rm -f "$TMP"
