#!/usr/bin/env bash
# Revisa si el servidor está bloqueando la IP de alguna red (fail2ban / crowdsec / firewall) y la desbloquea.
# Uso:  bash desbloquear-ips.sh            -> muestra bloqueos y libera los bloqueos web (no SSH)
#       bash desbloquear-ips.sh 181.x.x.x  -> además libera esa IP en todo y la deja en lista blanca
IP="${1:-}"
echo "== Conexiones recientes que el firewall rechazó (últimas IPs) =="
journalctl -k --since "-2h" 2>/dev/null | grep -oE "SRC=[0-9.]+" | sort | uniq -c | sort -rn | head -10 || true

if command -v fail2ban-client >/dev/null; then
  echo "== fail2ban =="
  for J in $(fail2ban-client status 2>/dev/null | sed -n 's/.*Jail list:\s*//p' | tr ',' ' '); do
    B=$(fail2ban-client status "$J" | sed -n 's/.*Banned IP list:\s*//p')
    echo "  [$J] bloqueadas: ${B:-ninguna}"
    if [ "$J" != "sshd" ] && [ -n "$B" ]; then for X in $B; do fail2ban-client set "$J" unbanip "$X" >/dev/null && echo "    liberada $X"; done; fi
    [ -n "$IP" ] && fail2ban-client set "$J" unbanip "$IP" >/dev/null 2>&1
  done
  if [ -n "$IP" ]; then
    mkdir -p /etc/fail2ban/jail.d
    echo -e "[DEFAULT]\nignoreip = 127.0.0.1/8 ::1 $IP" > /etc/fail2ban/jail.d/zz-localpro-blanca.conf
    fail2ban-client reload >/dev/null 2>&1 && echo "  $IP en lista blanca de fail2ban"
  fi
else
  echo "== fail2ban: no instalado =="
fi

if command -v cscli >/dev/null; then
  echo "== CrowdSec =="
  cscli decisions list 2>/dev/null | head -20
  [ -n "$IP" ] && cscli decisions delete --ip "$IP" && echo "  $IP liberada en CrowdSec"
fi

echo "== Reglas que bloquean IPs (iptables) =="
iptables -S 2>/dev/null | grep -E "DROP|REJECT" | grep -- "-s " | head -20
if [ -n "$IP" ]; then
  while iptables -C INPUT -s "$IP" -j DROP 2>/dev/null; do iptables -D INPUT -s "$IP" -j DROP; done
  while iptables -C INPUT -s "$IP" -j REJECT 2>/dev/null; do iptables -D INPUT -s "$IP" -j REJECT; done
  iptables -I INPUT -s "$IP" -p tcp -m multiport --dports 80,443 -j ACCEPT && echo "  $IP permitida en 80/443"
fi

if command -v ufw >/dev/null; then echo "== UFW =="; ufw status | head -20; fi
echo "== Prueba local =="
curl -s -o /dev/null -w "HTTPS responde: %{http_code}\n" --max-time 8 https://localpro.narinoexposhow.com/login
