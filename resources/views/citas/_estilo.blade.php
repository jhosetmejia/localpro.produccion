{{-- Estilo de las páginas para pacientes (pedir cita, confirmar, portal): diseño "Sonrisa" --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0e7490">
@if ($t->logo_url)<link rel="icon" href="{{ $t->logo_url }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<style>
*{box-sizing:border-box}
body{margin:0;font-family:'Plus Jakarta Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#083344;background:linear-gradient(170deg,#ecfeff 0%,#f0fdfa 45%,#fff 100%);min-height:100vh;-webkit-font-smoothing:antialiased}
.pg{max-width:620px;margin:0 auto;padding:22px 16px 48px}
.cab{display:flex;gap:14px;align-items:center;margin-bottom:18px}
.cab img,.cab .ini{width:58px;height:58px;border-radius:18px;object-fit:cover;background:#fff;box-shadow:0 8px 20px -10px rgba(8,51,68,.4)}
.cab .ini{display:grid;place-items:center;font-weight:800;font-size:1.5rem;color:#fff;background:linear-gradient(135deg,#22d3ee,#0891b2)}
.cab h1{font-size:1.25rem;margin:0;font-weight:800}.cab small{color:#0e7490;font-weight:700}
.card{background:#fff;border:1.5px solid #cdeff5;border-radius:22px;padding:18px;margin-bottom:14px;box-shadow:0 18px 40px -30px rgba(8,51,68,.35)}
.card h2{font-size:1.02rem;margin:0 0 12px;font-weight:800;display:flex;align-items:center;gap:8px}
.card h2 i{color:#0891b2}
label{display:block;font-size:.78rem;font-weight:800;color:#3d6470;margin:10px 0 4px}
input,select,textarea{width:100%;border:1.5px solid #cdeff5;border-radius:14px;padding:.75rem .85rem;font:inherit;font-size:1rem;color:#083344;background:#fff;outline:none}
input:focus,select:focus,textarea:focus{border-color:#22d3ee;box-shadow:0 0 0 3px #cffafe}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:16px;padding:.9rem 1.2rem;font:inherit;font-weight:800;font-size:1rem;cursor:pointer;text-decoration:none}
.btn.prim{background:linear-gradient(135deg,#67e8f9,#0891b2);color:#06303b;width:100%}
.btn.sec{background:#ecfeff;color:#0e7490;border:1.5px solid #a5f3fc}
.btn.peligro{background:#fff1f2;color:#be123c;border:1.5px solid #fecdd3}
.btn:disabled{opacity:.5;cursor:not-allowed}
.dias{display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;scrollbar-width:thin}
.dia{flex:none;width:64px;border:1.5px solid #cdeff5;border-radius:16px;padding:8px 4px;text-align:center;cursor:pointer;background:#fff;font-weight:800}
.dia small{display:block;font-size:.7rem;color:#3d6470;font-weight:700}
.dia b{display:block;font-size:1.3rem}
.dia.on{background:#0e7490;border-color:#0e7490;color:#fff}.dia.on small{color:#a5f3fc}
.horas{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:8px;margin-top:10px}
.hora{border:1.5px solid #cdeff5;border-radius:12px;padding:.6rem .3rem;text-align:center;font-weight:800;cursor:pointer;background:#fff}
.hora.on{background:#0891b2;color:#fff;border-color:#0891b2}
.err{background:#fff1f2;color:#9f1239;border:1.5px solid #fecdd3;border-radius:14px;padding:.7rem .9rem;font-weight:700;margin-bottom:12px}
.ok{background:#ecfdf5;color:#065f46;border:1.5px solid #a7f3d0;border-radius:14px;padding:.8rem .95rem;font-weight:700;margin-bottom:12px}
.muted{color:#3d6470;font-size:.88rem}
.pie{text-align:center;color:#5b7f89;font-size:.78rem;margin-top:22px}.pie a{color:#0e7490;font-weight:800;text-decoration:none}
.chk{display:flex;gap:10px;align-items:flex-start;font-weight:600;font-size:.86rem;color:#3d6470}
.chk input{width:20px;height:20px;flex:none;margin-top:2px}
.fila{display:flex;justify-content:space-between;gap:10px;padding:.6rem 0;border-bottom:1px dashed #d6eef3;align-items:center}
.fila:last-child{border-bottom:0}
.est{display:inline-block;border-radius:999px;padding:.15rem .6rem;font-size:.72rem;font-weight:800;color:#fff;white-space:nowrap}
.bar{height:7px;border-radius:9px;background:#e2f3f6;overflow:hidden;margin-top:6px}.bar i{display:block;height:100%;background:linear-gradient(90deg,#22d3ee,#0891b2)}
@media (max-width:480px){.g2{grid-template-columns:1fr}}
</style>
