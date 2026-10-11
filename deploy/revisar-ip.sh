#!/usr/bin/env bash
# Revisa si las conexiones de una IP (ej. un WiFi que no carga la página) llegan al servidor.
# Uso: bash revisar-ip.sh 176.57.207.106   (y en ese momento recargar la página en el equipo del WiFi)
IP="$1"; [ -z "$IP" ] && { echo "Falta la IP"; exit 1; }
# Corrige la lista blanca de fail2ban si quedó mal escrita
if [ -f /etc/fail2ban/jail.d/zz-localpro-blanca.conf ]; then
  echo -e "[DEFAULT]\nignoreip = 127.0.0.1/8 ::1 $IP" > /etc/fail2ban/jail.d/zz-localpro-blanca.conf
  fail2ban-client reload >/dev/null 2>&1; echo "Lista blanca fail2ban: $IP"
fi
command -v tcpdump >/dev/null || apt-get install -y -qq tcpdump >/dev/null 2>&1
echo "== Bloqueos del firewall para esa IP (últimas 24 h) =="
journalctl -k --since "-24h" 2>/dev/null | grep -c "SRC=$IP " | sed 's/^/Veces bloqueada: /'
echo "== Visitas de esa IP en el registro de la página =="
grep -h "^$IP " /home/*/logs/nginx/*access*.log /var/log/nginx/*access*.log 2>/dev/null | tail -3 || true
echo "== Escuchando 30 segundos: RECARGUE AHORA la página en el equipo del WiFi =="
timeout 30 tcpdump -ni any "host $IP" -c 8 2>/dev/null | tail -8
echo "== Fin =="
