#!/usr/bin/env bash
# Piloto automático de LocalPro: corre cada 2 minutos (cron /etc/cron.d/localpro-piloto, lo instala activar-piloto.sh).
# 1) Si hay código nuevo en GitHub (rama master), corre actualizar.sh (respaldo + vuelta atrás si falla).
#    Si una versión falla, no la reintenta en bucle: espera a que llegue una versión nueva.
# 2) Ejecuta las órdenes de mantenimiento que vengan en deploy/orden.txt (solo las de la lista de abajo, una sola vez cada una).
# 3) Escribe un reporte de estado (sin claves ni datos de clientes) en una dirección secreta para que Claude lo lea.
# Registro: /var/log/localpro-piloto.log
set -uo pipefail
exec 9>/var/lock/localpro-piloto.lock
flock -n 9 || exit 0   # si ya hay una actualización corriendo, esta vuelta se salta

# Carpeta del sitio de producción (la que atiende localproo.com)
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
USR="$(stat -c %U "$RAIZ")"
PHP="php8.2"
DOMINIO="localproo.com"
EST=/var/lib/localpro; mkdir -p "$EST"
LOGACT=/var/log/localpro-actualizacion.log
ahora() { date '+%F %T'; }
run() { sudo -u "$USR" "$@"; }
cd "$RAIZ" || exit 1

# ---------- 1) Código nuevo ----------
run git fetch -q origin master 2>/dev/null
LOCAL=$(run git rev-parse HEAD 2>/dev/null)
REMOTO=$(run git rev-parse origin/master 2>/dev/null)
FALLIDO=$(cat "$EST/commit-fallido" 2>/dev/null || true)

actualizar() {
  local TMP; TMP=$(mktemp)
  run git show origin/master:deploy/actualizar.sh > "$TMP" 2>/dev/null || cp "$RAIZ/deploy/actualizar.sh" "$TMP"
  echo "===== $(ahora) Actualizando a ${REMOTO:0:7} =====" >> "$LOGACT"
  if bash "$TMP" >> "$LOGACT" 2>&1; then
    echo "$(ahora) OK ${REMOTO:0:7}" > "$EST/ultima-actualizacion"; rm -f "$EST/commit-fallido"
    echo "$(ahora) Actualizado a ${REMOTO:0:7}"
  else
    echo "$(ahora) FALLÓ ${REMOTO:0:7} (se dejó la versión anterior)" > "$EST/ultima-actualizacion"
    echo "$REMOTO" > "$EST/commit-fallido"
    echo "$(ahora) Falló la actualización a ${REMOTO:0:7}; quedó la versión anterior"
  fi
  rm -f "$TMP"
}

if [ -n "$REMOTO" ] && [ "$LOCAL" != "$REMOTO" ] && [ "$REMOTO" != "$FALLIDO" ]; then
  actualizar
fi

# ---------- 2) Órdenes de mantenimiento ----------
# deploy/orden.txt tiene una línea:  ID|accion   (ej. 20261017-01|reiniciar). Cada ID se ejecuta una sola vez.
ORDEN=$(grep -v '^\s*#' "$RAIZ/deploy/orden.txt" 2>/dev/null | grep '|' | tail -1 | tr -d '\r')
OID=${ORDEN%%|*}; ACC=$(echo "${ORDEN#*|}" | tr -d ' ')
HECHA=$(cat "$EST/ultima-orden" 2>/dev/null || true)
if [ -n "$OID" ] && [ "$OID" != "$HECHA" ]; then
  echo "$OID" > "$EST/ultima-orden"
  FPM=$(systemctl list-units --type=service --state=running --no-legend 'php*-fpm*' | awk '{print $1}' | head -1); FPM=${FPM:-php8.2-fpm}
  R="hecha"
  case "$ACC" in
    reiniciar)
      for S in mysql mariadb; do systemctl list-unit-files "$S.service" >/dev/null 2>&1 && systemctl restart "$S"; done
      systemctl restart "$FPM"; nginx -t >/dev/null 2>&1 && systemctl restart nginx ;;
    reiniciar-php)   systemctl restart "$FPM" ;;
    reiniciar-nginx) nginx -t >/dev/null 2>&1 && systemctl restart nginx || R="nginx -t falló, no se reinició" ;;
    limpiar-cache)
      run $PHP artisan optimize:clear && run $PHP artisan config:cache && run $PHP artisan view:cache || R="falló" ;;
    quitar-mantenimiento) run $PHP artisan up || R="falló" ;;
    liberar-disco)
      ls -1dt /home/$USR/respaldos/*/ 2>/dev/null | grep -v '/diario/$' | tail -n +2 | xargs -r rm -rf
      find "$RAIZ/storage/logs" -name '*.log' -size +50M -exec truncate -s 0 {} \; 2>/dev/null
      journalctl --vacuum-size=200M >/dev/null 2>&1 ;;
    reintentar-actualizacion) rm -f "$EST/commit-fallido"; FALLIDO=""; actualizar ;;
    migrar) run $PHP artisan migrate --force >> "$LOGACT" 2>&1 || R="falló (ver registro de actualización)" ;;
    colas) run $PHP artisan queue:restart >/dev/null 2>&1 ;;
    refrescar-pruebas) bash "$RAIZ/deploy/refrescar-pruebas.sh" >> /var/log/localpro-pruebas.log 2>&1 || R="falló (ver /var/log/localpro-pruebas.log)" ;;
    diagnostico) : ;;  # solo refresca el reporte con más detalle
    *) R="acción desconocida, no se hizo nada" ;;
  esac
  echo "$(ahora) $OID $ACC: $R" > "$EST/resultado-orden"
  echo "$(ahora) Orden $OID ($ACC): $R"
fi

# ---------- 3) Reporte de estado ----------
TOKEN=$(cat /etc/localpro-estado-token 2>/dev/null || true)
[ -n "$TOKEN" ] || exit 0
F="$RAIZ/public/estado-$TOKEN.txt"
CODE=$(curl -s -o /dev/null -m 15 -w '%{http_code}' --resolve "$DOMINIO:443:127.0.0.1" -k "https://$DOMINIO/login")
limpio() { sed -E 's/(password|clave|secret|token|key)[^ ]*/\1=***/Ig' | cut -c1-300; }
N=12; [ "${ACC:-}" = "diagnostico" ] && N=60
set +o pipefail   # en el reporte, que no haya errores (grep sin resultados) no es una falla
{
  echo "REPORTE LOCALPRO · $(ahora) (hora del servidor)"
  echo "Sitio (/login): $CODE"
  echo "Versión en el servidor: $(run git log -1 --format='%h %s' 2>/dev/null | cut -c1-120)"
  echo "Versión en GitHub:      ${REMOTO:0:7}"
  echo "Última actualización:   $(cat "$EST/ultima-actualizacion" 2>/dev/null || echo 'ninguna aún')"
  echo "Última orden:           $(cat "$EST/resultado-orden" 2>/dev/null || echo 'ninguna')"
  echo "Disco: $(df -h --output=pcent,avail / | tail -1)   Memoria libre: $(free -m | awk '/Mem/{print $7" MB de "$2" MB"}')   Carga: $(cut -d' ' -f1-3 /proc/loadavg)"
  echo -n "Servicios:"; for S in nginx mysql mariadb $(systemctl list-units --type=service --no-legend 'php*-fpm*' | awk '{print $1}'); do
    systemctl list-unit-files "${S%.service}.service" >/dev/null 2>&1 && echo -n " ${S%.service}=$(systemctl is-active "${S%.service}")"; done; echo
  echo "Modo mantenimiento: $([ -f "$RAIZ/storage/framework/down" ] && echo SÍ || echo no)"
  ULTB=$(ls -1t /home/$USR/respaldos/diario/base-*.sql.gz 2>/dev/null | head -1)
  echo "Respaldo diario (en el servidor): $([ -n "$ULTB" ] && echo "$(basename "$ULTB") · $(du -h "$ULTB" | cut -f1)" || echo 'ninguno')"
  echo "Respaldo externo (fuera del servidor): $(cat /var/lib/localpro/respaldo-externo 2>/dev/null || echo 'NO ACTIVADO')"
  [ -s /var/lib/localpro/pruebas-estado ] && echo "Sitio de pruebas: $(cat /var/lib/localpro/pruebas-estado)"
  echo; echo "--- Vigilante (últimas líneas) ---"; tail -n "$N" /var/log/localpro-vigilante.log 2>/dev/null | limpio
  echo; echo "--- Última actualización (últimas líneas) ---"; tail -n "$N" "$LOGACT" 2>/dev/null | limpio
  echo; echo "--- Errores recientes de Laravel ---"
  grep -h '\.\(ERROR\|CRITICAL\|EMERGENCY\)' "$RAIZ/storage/logs/laravel"*.log 2>/dev/null | tail -n "$N" | limpio
  echo "(fin del reporte)"
} > "$F.tmp"
mv -f "$F.tmp" "$F"; chown "$USR:$USR" "$F"; chmod 644 "$F"
exit 0
