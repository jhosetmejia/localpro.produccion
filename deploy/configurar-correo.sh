#!/usr/bin/env bash
# Configura el correo que usa LocalPro para enviar los enlaces de recuperar contraseña.
# Uso (como root en la Consola web):
#   bash /home/localpro/htdocs/localpro.narinoexposhow.com/deploy/configurar-correo.sh
# Pregunta los datos SMTP (ej. Hostinger: smtp.hostinger.com, puerto 465, ssl) y envía un correo de prueba.
set -euo pipefail
RAIZ="/home/localpro/htdocs/localpro.narinoexposhow.com"
cd "$RAIZ"

read -rp "Servidor SMTP [smtp.hostinger.com]: " HOST; HOST=${HOST:-smtp.hostinger.com}
read -rp "Puerto [465]: " PORT; PORT=${PORT:-465}
read -rp "Cifrado ssl/tls [ssl]: " ENC; ENC=${ENC:-ssl}
read -rp "Correo (usuario): " USER
read -rsp "Contraseña del correo: " PASS; echo
read -rp "Nombre que verá el cliente [LocalPro]: " NOMBRE; NOMBRE=${NOMBRE:-LocalPro}

poner() { if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi; }
poner MAIL_MAILER smtp
poner MAIL_HOST "$HOST"
poner MAIL_PORT "$PORT"
poner MAIL_ENCRYPTION "$ENC"
poner MAIL_USERNAME "$USER"
poner MAIL_PASSWORD "\"$PASS\""
poner MAIL_FROM_ADDRESS "\"$USER\""
poner MAIL_FROM_NAME "\"$NOMBRE\""
chown localpro:localpro .env
sudo -u localpro php8.2 artisan config:cache >/dev/null

echo "Enviando correo de prueba a $USER ..."
if sudo -u localpro env DEST="$USER" php8.2 artisan tinker --execute='Mail::raw("El correo de LocalPro quedó configurado correctamente.", fn ($m) => $m->to(getenv("DEST"))->subject("Prueba LocalPro")); echo "OK";' | grep -q OK; then
  echo "LISTO. Revise la bandeja de $USER."
else
  echo "No se pudo enviar. Revise servidor, puerto y contraseña, y vuelva a ejecutar este script."
fi
