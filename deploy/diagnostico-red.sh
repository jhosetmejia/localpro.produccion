#!/usr/bin/env bash
# Revisa por qué el sitio no abre en algunas redes WiFi (DNS / IPv6 / puertos).
D=localpro.narinoexposhow.com
echo "== IP del servidor =="
echo "IPv4: $(curl -4 -s --max-time 5 https://api.ipify.org || echo '?')"
echo "IPv6: $(curl -6 -s --max-time 5 https://api64.ipify.org || echo 'sin IPv6')"
command -v dig >/dev/null || (apt-get install -y -qq dnsutils >/dev/null 2>&1)
for S in 8.8.8.8 1.1.1.1; do
  echo "== DNS de $D según $S =="
  echo "A    : $(dig +short A $D @$S | tr '\n' ' ')"
  echo "AAAA : $(dig +short AAAA $D @$S | tr '\n' ' ')"
done
echo "== DNS del dominio principal =="
echo "NS   : $(dig +short NS narinoexposhow.com @8.8.8.8 | tr '\n' ' ')"
echo "== Puertos que escucha el servidor web =="
ss -tlnp | grep -E ':(80|443)\s' | awk '{print $4}' | sort -u
echo "== Prueba IPv4 =="
curl -4 -s -o /dev/null -w "%{http_code}\n" --max-time 10 https://$D/login || echo FALLA
echo "== Prueba IPv6 =="
curl -6 -s -o /dev/null -w "%{http_code}\n" --max-time 10 https://$D/login || echo "FALLA (si arriba hay AAAA, este es el problema)"
