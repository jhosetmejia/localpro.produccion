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
  *) echo "Uso: localproo actualizar | artisan <comando> | ruta | logs [líneas] | respaldo | restaurar [AAAA-MM-DD] | probar-respaldo | llave" ;;
esac
