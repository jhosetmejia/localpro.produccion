#!/usr/bin/env bash
# 2026-11-28 (Jhoset): servidor TURN propio (coturn, gratis) para que las llamadas de LP Chat funcionen también con
# datos móviles. Se corre una vez con la orden "activar-llamadas" del piloto (como root). Se puede repetir sin problema.
# Deja LPCHAT_TURN_URL y LPCHAT_TURN_SECRET en el .env de producción y del sitio de pruebas. Resultado en /var/lib/localpro/llamadas.
set -uo pipefail
EST=/var/lib/localpro; mkdir -p "$EST"
fallo() { echo "falló: $1" > "$EST/llamadas"; echo "falló: $1"; exit 1; }

export DEBIAN_FRONTEND=noninteractive
command -v turnserver >/dev/null || { apt-get update -qq && apt-get install -y -qq coturn; } || fallo "no se pudo instalar coturn"

SEC=/etc/localpro-turn.secret
[ -s "$SEC" ] || openssl rand -hex 32 > "$SEC"
chmod 600 "$SEC"
IP=$(curl -s -4 -m 10 https://api.ipify.org || true)
[ -n "$IP" ] || IP=$(hostname -I | awk '{print $1}')
[ -n "$IP" ] || fallo "no se supo la IP del servidor"

cat > /etc/turnserver.conf <<CONF
# LP Chat (lo escribe deploy/activar-llamadas.sh)
listening-port=3478
fingerprint
use-auth-secret
static-auth-secret=$(cat "$SEC")
realm=localproo.com
external-ip=$IP
min-port=49160
max-port=49360
total-quota=60
stale-nonce=600
no-cli
no-multicast-peers
no-tlsv1
no-tlsv1_1
denied-peer-ip=0.0.0.0-0.255.255.255
denied-peer-ip=10.0.0.0-10.255.255.255
denied-peer-ip=127.0.0.0-127.255.255.255
denied-peer-ip=169.254.0.0-169.254.255.255
denied-peer-ip=172.16.0.0-172.31.255.255
denied-peer-ip=192.168.0.0-192.168.255.255
log-file=/var/log/turnserver.log
simple-log
CONF
[ -f /etc/default/coturn ] && { grep -q '^TURNSERVER_ENABLED' /etc/default/coturn && sed -i 's/^TURNSERVER_ENABLED=.*/TURNSERVER_ENABLED=1/' /etc/default/coturn || echo 'TURNSERVER_ENABLED=1' >> /etc/default/coturn; }
sed -i 's/^#\?TURNSERVER_ENABLED=.*/TURNSERVER_ENABLED=1/' /etc/default/coturn 2>/dev/null || true
systemctl enable coturn >/dev/null 2>&1
systemctl restart coturn || fallo "coturn no arrancó (ver /var/log/turnserver.log)"

# Firewall del VPS (si está activo)
if command -v ufw >/dev/null && ufw status 2>/dev/null | grep -q 'Status: active'; then
  ufw allow 3478/udp >/dev/null; ufw allow 3478/tcp >/dev/null; ufw allow 49160:49360/udp >/dev/null
fi

# .env de producción y pruebas
N=0
for d in /home/*/htdocs/*/; do
  d="${d%/}"; [ -f "$d/artisan" ] && [ -f "$d/.env" ] || continue
  grep -Eqs '^APP_URL="?https?://(www\.|pruebas\.)?localproo\.com/?"?$' "$d/.env" || continue
  for K in LPCHAT_TURN_URL LPCHAT_TURN_SECRET; do sed -i "/^$K=/d" "$d/.env"; done
  printf 'LPCHAT_TURN_URL=%s\nLPCHAT_TURN_SECRET=%s\n' "$IP" "$(cat "$SEC")" >> "$d/.env"
  U=$(stat -c %U "$d"); (cd "$d" && sudo -u "$U" php8.2 artisan config:cache >/dev/null 2>&1) || true
  N=$((N+1))
done

sleep 1
if systemctl is-active --quiet coturn; then
  echo "listo: coturn activo en $IP:3478 (relay 49160-49360/udp), .env actualizados: $N. Si las llamadas con datos no conectan, abrir esos puertos en el firewall de Hostinger (hPanel)." > "$EST/llamadas"
else
  fallo "coturn no quedó activo"
fi
cat "$EST/llamadas"
