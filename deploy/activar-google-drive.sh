#!/usr/bin/env bash
# 2026-11-25 (Jhoset): conecta Google Drive (15 GB gratis) como segunda copia de los respaldos de LocalPro, PFN y NES.
# Se corre UNA vez, como root en la Consola web de hPanel:
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/activar-google-drive.sh -o /root/activar-drive.sh && bash /root/activar-drive.sh
# Pide el "token" de Google: se saca en el computador con rclone (ver los pasos que muestra). Queda en la carpeta "Respaldos LocalPro" del Drive.
set -uo pipefail
[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
command -v rclone >/dev/null 2>&1 || { echo "==> Instalando rclone"; curl -fsSL https://rclone.org/install.sh | bash >/dev/null || { echo "No se pudo instalar rclone."; exit 1; }; }
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"

echo
echo "=================================================================="
echo " EN EL COMPUTADOR (Windows, PowerShell), una sola vez:"
echo "   1) winget install Rclone.Rclone"
echo "   2) Cierre y abra PowerShell, y escriba:  rclone authorize \"drive\" \"eyJzY29wZSI6ImRyaXZlLmZpbGUifQ\""
echo "   3) Se abre Google: entre con la cuenta del Drive y dé Permitir."
echo "   4) En PowerShell sale un texto entre ---> y <---End paste."
echo "      Cópielo COMPLETO y péguelo aquí abajo."
echo "=================================================================="
read -r -p "Token: " TOKEN
TOKEN="$(echo "$TOKEN" | tr -d '[:space:]')"
# rclone nuevo entrega un bloque en base64 ({"client_id":"","client_secret":"","token":"{...}"}): se saca el token de adentro
case "$TOKEN" in \{*) ;; *)
  TOKEN="$(python3 -c 'import sys,json,base64;b=sys.argv[1];b+="="*(-len(b)%4);d=json.loads(base64.urlsafe_b64decode(b));print(d.get("token",""))' "$TOKEN" 2>/dev/null)" ;;
esac
case "$TOKEN" in \{*access_token*\}) ;; *) echo "Eso no parece el token (debe empezar con { y terminar con }). No se cambió nada."; exit 1 ;; esac

# Se escribe la conexión directo en el archivo de rclone (con "config create" rclone se queda esperando un navegador)
rclone config delete gdrive >/dev/null 2>&1
CONF="$(rclone config file 2>/dev/null | tail -1)"; [ -n "$CONF" ] || CONF=/root/.config/rclone/rclone.conf
mkdir -p "$(dirname "$CONF")"
printf '\n[gdrive]\ntype = drive\nscope = drive.file\ntoken = %s\n' "$TOKEN" >> "$CONF" || { echo "No se pudo guardar la conexión."; exit 1; }
chmod 600 "$CONF" 2>/dev/null

echo "==> Probando Google Drive"
echo "prueba $(date)" > /tmp/lp-drive.txt
if ! rclone copy /tmp/lp-drive.txt "gdrive:Respaldos LocalPro/prueba/"; then
  rclone config delete gdrive >/dev/null 2>&1
  echo "!! Google Drive no aceptó el token. Vuelva a sacarlo y corra el comando otra vez."; exit 1
fi
rclone purge "gdrive:Respaldos LocalPro/prueba" >/dev/null 2>&1; rm -f /tmp/lp-drive.txt
[ -s /etc/localpro-respaldo.key ] || { openssl rand -base64 48 | tr -d '\n' > /etc/localpro-respaldo.key; chmod 600 /etc/localpro-respaldo.key; NUEVA=1; }

echo "==> Primera copia ahora (las fotos pueden tardar un rato la primera vez)"
bash "$RAIZ/deploy/respaldo-diario.sh" >> /var/log/localpro-respaldo.log 2>&1
bash "$RAIZ/deploy/respaldo-sistemas.sh" >> /var/log/localpro-respaldo.log 2>&1
echo "LocalPro:  $(cat /var/lib/localpro/respaldo-externo 2>/dev/null)"
echo "PFN y NES: $(cat /var/lib/localpro/respaldo-sistemas 2>/dev/null)"
echo
echo "=================================================================="
echo " GOOGLE DRIVE CONECTADO: cada noche se guarda ahí una copia cifrada"
echo " de LocalPro, PFN y NES (30 días) y las fotos de PFN y NES."
if [ "${NUEVA:-0}" = 1 ]; then
echo
echo " GUARDE ESTA LLAVE (sin ella las copias no se pueden abrir):"
echo "   $(cat /etc/localpro-respaldo.key)"
fi
echo "=================================================================="
