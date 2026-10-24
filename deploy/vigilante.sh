#!/usr/bin/env bash
# Vigilante de LocalPro: corre cada 5 minutos (cron /etc/cron.d/localpro-vigilante, lo instala actualizar.sh).
# 1) Si el disco pasa del 85 %, borra respaldos viejos y registros grandes.
# 2) Si la página no responde, reinicia MySQL, PHP y nginx (en ese orden, solo lo que haga falta) y lo anota.
# Registro: /var/log/localpro-vigilante.log
set -uo pipefail
# Carpeta del sitio de producción (la que atiende localproo.com)
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
USR="$(stat -c %U "$RAIZ")"
RESP="/home/$USR/respaldos"
DOMINIO="localproo.com"
ahora() { date '+%F %T'; }

# --- Disco ---
USO=$(df --output=pcent / | tail -1 | tr -dc '0-9')
if [ "${USO:-0}" -ge 85 ]; then
  echo "$(ahora) Disco al ${USO}%: limpiando"
  ls -1dt "$RESP"/*/ 2>/dev/null | grep -v '/diario/$' | tail -n +2 | xargs -r rm -rf
  find "$RESP/diario" -name '*.gz' -mtime +7 -delete 2>/dev/null
  find "$RAIZ/storage/logs" -name '*.log' -size +100M -exec truncate -s 0 {} \; 2>/dev/null
  journalctl --vacuum-size=200M >/dev/null 2>&1
  echo "$(ahora) Disco ahora al $(df --output=pcent / | tail -1 | tr -d ' ')"
fi

# --- Sitio ---
probar() { curl -s -o /dev/null -m 20 -w '%{http_code}' --resolve "$DOMINIO:443:127.0.0.1" -k "https://$DOMINIO/login"; }
CODE=$(probar)
[ "$CODE" = "200" ] && exit 0

# Falla: una segunda prueba para no reiniciar por un tropiezo de un segundo
sleep 15
CODE=$(probar)
[ "$CODE" = "200" ] && exit 0
echo "$(ahora) La página respondió $CODE. Revisando servicios..."

FPM=$(systemctl list-units --type=service --state=running --no-legend 'php*-fpm*' | awk '{print $1}' | head -1)
FPM=${FPM:-php8.2-fpm}

for SRV in mysql mariadb; do
  if systemctl list-unit-files "$SRV.service" >/dev/null 2>&1 && ! systemctl is-active --quiet "$SRV"; then
    echo "$(ahora) $SRV estaba caído: reiniciando"; systemctl restart "$SRV"; sleep 5
  fi
done
CODE=$(probar); [ "$CODE" = "200" ] && { echo "$(ahora) Sitio arriba otra vez"; exit 0; }

echo "$(ahora) Reiniciando $FPM"; systemctl restart "$FPM"; sleep 5
CODE=$(probar); [ "$CODE" = "200" ] && { echo "$(ahora) Sitio arriba otra vez"; exit 0; }

echo "$(ahora) Reiniciando nginx"; nginx -t >/dev/null 2>&1 && systemctl restart nginx; sleep 3
CODE=$(probar); [ "$CODE" = "200" ] && { echo "$(ahora) Sitio arriba otra vez"; exit 0; }

# Modo mantenimiento olvidado de una actualización a medias
if [ -f "$RAIZ/storage/framework/down" ]; then
  echo "$(ahora) Estaba en modo mantenimiento: lo quito"; sudo -u "$USR" php "$RAIZ/artisan" up
fi
echo "$(ahora) Sigue respondiendo $(probar). Últimos errores:"
tail -n 5 "$RAIZ/storage/logs/laravel.log" 2>/dev/null
dmesg 2>/dev/null | grep -i 'out of memory\|killed process' | tail -n 3
