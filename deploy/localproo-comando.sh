#!/usr/bin/env bash
# Comando corto para el VPS: "localproo actualizar", "localproo artisan …", "localproo ruta".
# Lo instala solo deploy/actualizar.sh en /usr/local/bin/localproo. Encuentra la carpeta del sitio (la que atiende localproo.com).
RAIZ="$(for d in /home/localproo/htdocs/localproo.com /home/*/htdocs/*/; do d="${d%/}"; [ -f "$d/artisan" ] && grep -Eqs '^APP_URL="?https?://(www\.)?localproo\.com/?"?$' "$d/.env" && { echo "$d"; break; }; done)"
[ -n "$RAIZ" ] || { echo "No encontré la carpeta de LocalProo."; exit 1; }
U="$(stat -c %U "$RAIZ")"
case "${1:-}" in
  actualizar) shift; exec bash "$RAIZ/deploy/actualizar.sh" "$@" ;;
  artisan) shift; cd "$RAIZ" && exec sudo -u "$U" php8.2 artisan "$@" ;;
  ruta) echo "$RAIZ" ;;
  logs) exec tail -n "${2:-80}" "$RAIZ/storage/logs/laravel.log" ;;
  respaldo) exec bash "$RAIZ/deploy/respaldo-diario.sh" ;;
  restaurar) shift; exec bash "$RAIZ/deploy/restaurar.sh" "$@" ;;
  probar-respaldo) exec bash "$RAIZ/deploy/probar-respaldo.sh" ;;
  llave) echo "Llave de cifrado de las copias externas (guárdela fuera del servidor, NO la mande por chat):"; echo; cat /etc/localpro-respaldo.key 2>/dev/null || echo "(no está activado el respaldo externo)"; echo ;;
  espacio)
    echo "== Disco"; df -h / | tail -1
    echo "== Lo que más ocupa"
    du -xsh /home/*/htdocs/* /home/*/respaldos /home/*/respaldos/* /root/migracion-* /root/sitio-viejo-* /var/lib/mysql /var/log /var/cache/apt /tmp 2>/dev/null | sort -rh | head -20
    echo "== Fotos y videos subidos"; du -sh "$RAIZ/storage/app/public" 2>/dev/null
    echo "== Logs de Laravel"; du -sh "$RAIZ/storage/logs" 2>/dev/null
    echo "== Registros binarios de MySQL"; du -ch /var/lib/mysql/binlog.* 2>/dev/null | tail -1
    ;;
  limpiar)
    echo "Liberando espacio (sin tocar la base de datos ni lo que subieron los negocios)..."
    U2="$(stat -c %U "$RAIZ")"
    ls -1dt /home/$U2/respaldos/*/ 2>/dev/null | grep -v '/diario/$' | tail -n +2 | xargs -r rm -rf
    find /home/$U2/respaldos/diario -name 'archivos-*.tar.gz' -mtime +3 -delete 2>/dev/null
    LOG="$RAIZ/storage/logs/laravel.log"; [ -f "$LOG" ] && tail -n 20000 "$LOG" > "$LOG.tmp" && cat "$LOG.tmp" > "$LOG" && rm -f "$LOG.tmp"
    journalctl --vacuum-size=200M >/dev/null 2>&1
    apt-get clean >/dev/null 2>&1
    mysql -e "PURGE BINARY LOGS BEFORE NOW() - INTERVAL 3 DAY" 2>/dev/null
    df -h / | tail -1
    ;;
  *) echo "Uso: localproo actualizar | artisan <comando> | ruta | logs [líneas] | respaldo | restaurar [AAAA-MM-DD] | probar-respaldo | llave | espacio | limpiar" ;;
esac
