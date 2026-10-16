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
- VPS Hostinger (CloudPanel): `/home/localproo/htdocs/localproo.com`, PHP 8.2.
- Actualizar producción (como root en la consola web de hPanel), hace respaldo de BD + código y vuelve atrás si falla:
  `curl -fsSL https://raw.githubusercontent.com/jhosetmejia/localpro.produccion/master/deploy/actualizar.sh | bash`
- Otros scripts en `deploy/` (instalar VPS, correo, respaldo diario, dominio nuevo, reset admin).
- Pagos con Wompi (`wompiController`); integración Meta con webhook (`metaWebhookController`).

## Reglas del producto
- Todo en español (incluidos mensajes de validación).
- Nunca ventanas nativas del navegador (`alert/confirm/prompt`): usar el aviso propio con el diseño del sitio.
- Cuidar datos tributarios (UVT, tablas de retención con vigencias): citar la norma/fuente en guías y blog.
