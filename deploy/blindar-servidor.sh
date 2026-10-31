#!/bin/bash
# Blindaje del servidor (lo corre actualizar.sh en cada despliegue; si algo sale mal, deja todo como estaba).
#  1. nginx nunca ejecuta PHP dentro de /storage ni de las carpetas de imágenes/audio (aunque alguien logre subir un .php).
#  2. nginx no entrega archivos ocultos (.env, .git, .htaccess…).
#  3. Producción siempre con APP_DEBUG=false (un error nunca muestra claves ni código).
# Uso: blindar-servidor.sh <carpeta-del-sitio> <dominio> <0|1 pruebas>
RAIZ="$1"; DOMINIO="$2"; PRUEBAS="${3:-0}"
[ -d "$RAIZ" ] && [ -n "$DOMINIO" ] || exit 0

# 3. APP_DEBUG apagado
if grep -q '^APP_DEBUG=true' "$RAIZ/.env" 2>/dev/null; then
  sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' "$RAIZ/.env"
  echo "    Seguridad: APP_DEBUG estaba encendido; quedó apagado."
fi
chmod 640 "$RAIZ/.env" 2>/dev/null

# 1 y 2. Reglas de nginx
command -v nginx >/dev/null || exit 0
SNIP=/etc/nginx/snippets/localpro-blindaje.conf
mkdir -p /etc/nginx/snippets
cat > "$SNIP.nuevo" <<'NG'
# LocalProo: nunca ejecutar PHP en carpetas de archivos subidos o estáticos
location ~* ^/(storage|img|audio|fonts|icons|assets|js|css|uploads)/.*\.(php[0-9]?|phtml|phar|pht|phps|cgi|pl|py|sh)$ { return 404; }
# Archivos ocultos y de configuración: nunca se entregan
location ~ /\.(?!well-known/) { return 404; }
location ~* \.(env|sql|sql\.gz|bak|old|orig|swp|log|sh)$ { return 404; }
NG
mv "$SNIP.nuevo" "$SNIP"

VHOST=$(grep -lsE "server_name[^;]*\b$(echo "$DOMINIO" | sed 's/\./\\./g')\b" /etc/nginx/sites-enabled/* 2>/dev/null | head -1)
[ -f "$VHOST" ] || { echo "    Seguridad: no encontré la configuración de nginx de $DOMINIO (se omite)."; exit 0; }
if ! grep -q "localpro-blindaje.conf" "$VHOST"; then
  cp "$VHOST" "/root/vhost-$DOMINIO-antes-blindaje.conf"
  # Va justo después de cada "root ..." (antes de las reglas de PHP de CloudPanel, así gana)
  sed -i -E "/^\s*root\s+[^;]+;/a\  include $SNIP;" "$VHOST"
fi
if nginx -t >/dev/null 2>&1; then
  systemctl reload nginx 2>/dev/null || nginx -s reload 2>/dev/null
  echo "    Seguridad: reglas de nginx activas (sin PHP en /storage, sin archivos ocultos)."
else
  echo "!!  Seguridad: nginx no aceptó las reglas; se deja como estaba."
  [ -f "/root/vhost-$DOMINIO-antes-blindaje.conf" ] && cp "/root/vhost-$DOMINIO-antes-blindaje.conf" "$VHOST"
  nginx -t >/dev/null 2>&1 && (systemctl reload nginx 2>/dev/null || nginx -s reload 2>/dev/null)
fi
exit 0
