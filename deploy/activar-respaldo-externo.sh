#!/usr/bin/env bash
# Activa la copia diaria FUERA del servidor en Backblaze B2 (10 GB gratis). Se corre UNA vez, como root en la Consola web de hPanel:
#   curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/activar-respaldo-externo.sh -o /root/activar-respaldo.sh && bash /root/activar-respaldo.sh
# Le pide las llaves de Backblaze aquí mismo (no se escriben en el chat ni en GitHub).
set -uo pipefail
[ "$(id -u)" = 0 ] || { echo "Ejecute como root"; exit 1; }
RAIZ="$( [ -d /home/localproo/htdocs/localproo.com ] && echo /home/localproo/htdocs/localproo.com || echo /home/localpro/htdocs/localpro.narinoexposhow.com )"

if ! command -v rclone >/dev/null 2>&1; then
  echo "==> Instalando rclone"
  curl -fsSL https://rclone.org/install.sh | bash >/dev/null || { echo "No se pudo instalar rclone."; exit 1; }
fi

echo
echo "Pegue los datos de Backblaze (B2 → Application Keys → Add a New Application Key):"
read -r -p "  keyID: " KID
read -r -s -p "  applicationKey (no se ve al pegar): " AKEY; echo
read -r -p "  Nombre del bucket (ej. localpro-respaldos): " BUCKET
[ -n "$KID" ] && [ -n "$AKEY" ] && [ -n "$BUCKET" ] || { echo "Faltan datos. No se cambió nada."; exit 1; }

rclone config create respaldo b2 account "$KID" key "$AKEY" hard_delete true >/dev/null || { echo "No se pudo guardar la configuración."; exit 1; }
chmod 600 /root/.config/rclone/rclone.conf 2>/dev/null
echo "respaldo:$BUCKET/localpro" > /etc/localpro-respaldo.destino

if [ ! -s /etc/localpro-respaldo.key ]; then
  openssl rand -base64 48 | tr -d '\n' > /etc/localpro-respaldo.key; chmod 600 /etc/localpro-respaldo.key
  NUEVA=1
fi

echo "==> Probando la conexión con Backblaze"
echo "prueba $(date)" > /tmp/lp-prueba.txt
if ! rclone copy /tmp/lp-prueba.txt "respaldo:$BUCKET/localpro/prueba/"; then
  echo "!! Backblaze no aceptó las llaves o el bucket no existe. Revise y vuelva a correr el comando."; exit 1
fi
rclone delete "respaldo:$BUCKET/localpro/prueba/" >/dev/null 2>&1; rm -f /tmp/lp-prueba.txt

echo "==> Haciendo la primera copia ahora (puede tardar unos minutos)"
bash "$RAIZ/deploy/respaldo-diario.sh" >> /var/log/localpro-respaldo.log 2>&1
cat /var/lib/localpro/respaldo-externo 2>/dev/null

echo
echo "=================================================================="
echo " RESPALDO EXTERNO ACTIVO: cada noche (3:15 a. m.) se sube una copia"
echo " cifrada a Backblaze y se guardan 30 días."
if [ "${NUEVA:-0}" = 1 ]; then
echo
echo " GUARDE ESTA LLAVE EN UN LUGAR SEGURO (gestor de claves o papel)."
echo " Sin ella las copias NO se pueden abrir si el servidor se pierde."
echo " No la mande por el chat."
echo
echo "   $(cat /etc/localpro-respaldo.key)"
fi
echo "=================================================================="
