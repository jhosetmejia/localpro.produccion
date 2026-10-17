#!/usr/bin/env bash
# Baja y descifra una copia externa a /root/restaurar/AAAA-MM-DD (NO toca el sistema en vivo).
# Uso: bash restaurar-respaldo-externo.sh 2026-10-18
set -uo pipefail
DIA="${1:-$(date +%Y-%m-%d)}"; DESTINO="$(cat /etc/localpro-respaldo.destino)"; OUT="/root/restaurar/$DIA"
mkdir -p "$OUT" && rclone copy "$DESTINO/$DIA/" "$OUT/" || { echo "No hay copia de $DIA"; exit 1; }
for f in "$OUT"/*.enc; do openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass file:/etc/localpro-respaldo.key -in "$f" -out "${f%.enc}" && rm -f "$f"; done
ls -lh "$OUT"; echo "Listo: archivos descifrados en $OUT (el sistema en vivo no se tocó)."
