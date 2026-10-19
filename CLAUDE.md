# LocalPro (LocalProo) — sistema

Software de gestión para negocios de Jhoset Mejía (DiCreWeb): POS/caja, inventario, ventas, compras, facturación,
contabilidad para contadores (DIAN, IVA, retenciones, formulario 350), nómina, alquiler, obituarios (funerarias),
catálogo/tienda, red comercial de asesores con curso, blog SEO, prospectos por correo.
Sitio en vivo: https://localproo.com

## Cómo trabajar con Jhoset
- Responder SIEMPRE en español, informal, corto y directo.
- No hacer cosas que le pidan aprobar a cada rato: preferir un solo comando/script.
- NUNCA dar comandos con placeholders; siempre valores reales completos.
- No tocar lo que no pidió. Al terminar: commit + push y avisar en 2-3 líneas.
- LocalPro es referencia: a veces pide replicar sus herramientas (p. ej. "Mis enlaces" tipo Linktree) en NES o PFN.

## Stack y despliegue
- Laravel 12, PHP 8.2, Blade, Spatie Permission, Sanctum. BD MySQL en el VPS.
- Repo `jhosetmejia/localpro.produccion`, rama **`master`**.
- VPS Hostinger (CloudPanel, 2.25.153.240): **PRODUCCIÓN REAL = `/home/localpro/htdocs/localpro.narinoexposhow.com`** (site user `localpro`, BD MySQL `localpro`).
  Aunque la carpeta se llame "narinoexposhow", su nginx atiende `localproo.com` y `www.localproo.com` (DNS en Cloudflare). NUNCA borrar esa carpeta, ese sitio de CloudPanel ni la BD `localpro`.
  (Verificado 2026-10-16: `/home/localproo` NO existe en el VPS. El subdominio DNS `localpro.narinoexposhow.com` se quitó, pero el sitio sigue siendo el de producción.)
- Actualizar producción (como root en la consola web de hPanel), hace respaldo de BD + código y vuelve atrás si falla:
  `curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/actualizar.sh | bash`
- **Piloto automático** (se activa una vez con `curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/activar-piloto.sh | bash`):
  cron cada 2 min (`deploy/piloto-automatico.sh`) que, si hay commit nuevo en `master`, corre `actualizar.sh` (con respaldo y vuelta atrás);
  si una versión falla no la reintenta hasta que llegue otra. O sea: **push a master = despliegue en ≤ 2-5 min**.
  - Mantenimiento sin consola: editar la última línea de `deploy/orden.txt` (`ID|accion`, ID nuevo cada vez) y hacer push.
    Acciones: reiniciar, reiniciar-php, reiniciar-nginx, limpiar-cache, quitar-mantenimiento, liberar-disco, reintentar-actualizacion, migrar, colas, diagnostico.
  - Reporte de estado (sitio, versión, último despliegue, última orden, disco, servicios, vigilante, errores Laravel) en
    `https://localproo.com/estado-<token>.txt` (token en `/etc/localpro-estado-token`; Jhoset pasa la URL en el chat). Leerlo con WebFetch.
  - Desde la nube de Claude NO hay SSH al VPS (bloqueado): todo va por GitHub + este piloto. El vigilante (cada 5 min) reinicia servicios solo si el sitio se cae.
- **Sitio de pruebas (staging): https://pruebas.localproo.com** — rama **`staging`**, carpeta `/home/localpro-pruebas/htdocs/pruebas.localproo.com`,
  BD `localpro_pruebas` (copia de producción), pide usuario `localpro` + clave del navegador (`CLAVE_PRUEBAS`), sin correos (MAIL_MAILER=log),
  sin Wompi ni Meta, DIAN en modo práctica. Lo crea `deploy/activar-pruebas.sh`; `deploy/piloto-pruebas.sh` (cron cada 2 min) lo actualiza con `staging`.
  **Flujo de trabajo: los cambios van primero a `staging`; cuando Jhoset los aprueba, se pasan a `master` (fast-forward) y se despliegan solos a producción.**
  Arreglos urgentes de producción pueden ir directo a `master` (y luego `master` → `staging`). Orden `refrescar-pruebas` recopia los datos de producción.
- **Respaldo externo**: `deploy/activar-respaldo-externo.sh` (rclone → Backblaze B2, remoto `respaldo`, destino en `/etc/localpro-respaldo.destino`).
  Cada noche `respaldo-diario.sh` (base + storage/app/public + firmas) llama a `respaldo-externo.sh`: cifra con AES-256 (llave `/etc/localpro-respaldo.key`,
  Jhoset la guarda aparte) y conserva 30 días. Restaurar: `deploy/restaurar-respaldo-externo.sh AAAA-MM-DD` (solo baja y descifra, no toca producción).
  El estado de ambos respaldos y del sitio de pruebas sale en el reporte del servidor.
- Otros scripts en `deploy/` (instalar VPS, correo, respaldo diario, dominio nuevo, reset admin).
- Pagos con Wompi (`wompiController`); integración Meta con webhook (`metaWebhookController`).

## Reglas del producto
- Todo en español (incluidos mensajes de validación).
- Nunca ventanas nativas del navegador (`alert/confirm/prompt`): usar el aviso propio con el diseño del sitio.
- **Venezuela va en dólares** (`app/Support/Moneda.php`, `Moneda::PAISES_USD`): su tienda vende en USD (Paises), el plan se cobra en USD (`planes.precio_usd`; Wompi cobra el equivalente en pesos con la tasa `tasa_usd_cop`, guardado en `pagos_suscripcion.cobro_cop`) y sus comisiones se generan en USD (`comisiones.moneda`, ajustes `red_usd_*`). Nunca formatear plata con `'$' . number_format(...)`: usar `dinero()` (tienda), `Moneda::formato($v, $moneda)` (planes/red) o `$asesor->plata($v)`.
- Cuidar datos tributarios (UVT, tablas de retención con vigencias): citar la norma/fuente en guías y blog.
