@include('layouts.partials.landing-head')
<meta name="robots" content="noindex">
<style>
    :root{--g:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);--k:#0b0d0c;--ink:#151a17;--mut:#5b665e;--line:#e3efe5;--bg:#f4f8f5;--lime:#a6f34a}
    *{box-sizing:border-box}
    body{margin:0;font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;line-height:1.55}
    .w{max-width:760px;margin:0 auto;padding:0 16px 48px}
    .top{display:flex;align-items:center;justify-content:space-between;padding:16px 0;gap:10px}
    .brand{display:flex;align-items:center;gap:.5rem;font-weight:800;text-decoration:none;color:inherit;font-size:1.1rem}
    .brand b{width:32px;height:32px;border-radius:10px;background:var(--k);color:var(--lime);display:grid;place-items:center}
    .top a.salir{color:var(--mut);font-weight:700;text-decoration:none;font-size:.9rem}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border:0;border-radius:999px;padding:.85rem 1.3rem;font-weight:800;text-decoration:none;cursor:pointer;font-family:inherit;font-size:1rem}
    .btn-g{background:var(--g);color:var(--k)}.btn-k{background:var(--k);color:#fff}.btn-l{background:#fff;border:1.5px solid var(--line);color:var(--ink)}.btn-w{background:#25d366;color:#fff}
    .btn-block{width:100%}
    .hero{background:var(--k);color:#fff;border-radius:28px;padding:28px 22px;position:relative;overflow:hidden;margin-bottom:16px}
    .hero::after{content:"";position:absolute;right:-70px;top:-70px;width:230px;height:230px;border-radius:50%;background:var(--g);opacity:.35;filter:blur(8px)}
    .hero small{color:var(--lime);font-weight:800;letter-spacing:.12em;text-transform:uppercase;font-size:.72rem;position:relative;z-index:1}
    .hero h1{font-size:clamp(1.7rem,6vw,2.5rem);line-height:1.08;margin:.35rem 0 .6rem;letter-spacing:-.03em;position:relative;z-index:1}
    .hero h1 em{font-style:normal;background:var(--g);-webkit-background-clip:text;background-clip:text;color:transparent}
    .hero p{color:#cfd8d2;margin:0;position:relative;z-index:1}
    .card{background:#fff;border:1.5px solid var(--line);border-radius:24px;padding:22px;margin-bottom:14px}
    .barra{height:10px;border-radius:99px;background:#eef3ef;overflow:hidden;margin:8px 0 4px}
    .barra div{height:100%;background:var(--g);border-radius:99px}
    .paso{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px dashed var(--line);text-decoration:none;color:inherit}
    .paso:last-child{border-bottom:0}
    .paso .ic{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:#eef3ef;color:var(--mut);flex:none}
    .paso.ok .ic{background:var(--g);color:var(--k)}
    .paso.ya .ic{background:var(--k);color:var(--lime)}
    .paso .t{flex:1;min-width:0}.paso .t b{display:block}.paso .t small{color:var(--mut);font-weight:600}
    .paso.bloq{opacity:.5;pointer-events:none}
    .msg{border-radius:18px;padding:14px 16px;font-weight:700;margin-bottom:14px}
    .msg.ok{background:#e8f8ec;color:#11702f}.msg.mal{background:#fff5d6;color:#8a6400}
    .puntos{list-style:none;padding:0;margin:14px 0}
    .puntos li{display:flex;gap:10px;padding:9px 0;border-bottom:1px dashed var(--line);font-weight:500}
    .puntos li:last-child{border-bottom:0}
    .puntos li i{color:#16b34a;margin-top:4px;flex:none}
    .clave{background:var(--g);border-radius:18px;padding:14px 16px;font-weight:800;color:var(--k)}
    .preg{margin-bottom:12px}
    .preg h3{font-size:1.02rem;margin:0 0 10px}
    .op{display:flex;gap:10px;align-items:flex-start;border:1.5px solid var(--line);border-radius:14px;padding:11px 13px;margin-bottom:8px;cursor:pointer;font-weight:600}
    .op input{accent-color:#16b34a;width:18px;height:18px;margin-top:2px;flex:none}
    .op:has(input:checked){border-color:#16b34a;background:#f3fcf5}
    .pie{text-align:center;color:var(--mut);font-size:.8rem;margin-top:22px}
</style>
