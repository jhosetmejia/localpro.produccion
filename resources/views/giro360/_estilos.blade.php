{{-- Estilos compartidos de Giro 360 (página principal y página de audio) --}}
<style>
*{box-sizing:border-box}
html,body{margin:0}
[hidden]{display:none!important}
img{max-width:100%}
/* Una sola columna tipo app de cámara, look de pista de evento (oscuro a propósito) */
:root{
  --bg:#0b0f0c; --surface:#131c16; --surface2:#1b2920; --line:#29402f;
  --fg:#f2fbf4; --muted:#9fbaa8; --accent:#2fd15a; --accent2:#a6f34a; --ok:#a6f34a; --err:#ff8a7a;
  --display:'Unbounded',system-ui,sans-serif; --body:'Figtree',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
  color-scheme:dark;
}
html,body{background:var(--bg);color:var(--fg);overflow-x:hidden;max-width:100%}
.app>*,.card>*{min-width:0}
body{font-family:var(--body);font-size:16px;line-height:1.45;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px));padding-block:18px 40px}
.app{max-width:520px;margin:0 auto;display:flex;flex-direction:column;gap:18px}
header{display:flex;align-items:center;justify-content:space-between;gap:12px}
.brand{font-family:var(--display);font-weight:800;font-size:1.35rem;letter-spacing:-.01em;display:flex;align-items:center;gap:10px}
.brand i{width:34px;height:34px;border-radius:50%;background:conic-gradient(from 0deg,var(--accent),var(--accent2),var(--accent));display:grid;place-items:center;font-style:normal;font-size:.75rem;color:var(--bg)}
.chip{font-size:.8rem;font-weight:600;color:var(--muted);background:var(--surface);border:1px solid var(--line);border-radius:999px;padding:6px 12px}
.chip:focus-visible,.btn:focus-visible,.fx:focus-visible{outline:2px solid var(--accent2);outline-offset:2px}
h2{font-family:var(--display);font-weight:600;font-size:1rem;margin:0;letter-spacing:.01em}
.lbl{font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.card{background:var(--surface);border:1px solid var(--line);border-radius:22px;padding:16px;display:flex;flex-direction:column;gap:14px}
.btn{appearance:none;border:0;border-radius:16px;padding:14px 16px;font:600 1rem var(--body);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;text-align:center;color:var(--fg);background:var(--surface2)}
a.btn{text-decoration:none}
.btn:disabled{opacity:.5;cursor:default}
.btn.big{font-family:var(--display);font-size:1.15rem;padding:22px 16px;border-radius:22px;color:#0b0d0c;background:linear-gradient(120deg,var(--accent2),var(--accent) 55%,#16b34a)}
.btn.pri{background:var(--accent);color:#0b0d0c}
.btn.ghost{background:transparent;border:1px solid var(--line)}
.row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fxs{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px}
.fx{background:var(--surface2);border:1.5px solid transparent;border-radius:16px;padding:10px 10px 8px;cursor:pointer;text-align:left;color:var(--fg);font:inherit;display:flex;flex-direction:column;gap:4px}
.fx[aria-pressed="true"]{border-color:var(--accent);background:#1f3a27}
.fx b{font-size:.92rem}
.fx small{color:var(--muted);font-size:.75rem}
.fx svg{width:100%;height:38px;display:block}
.field{display:flex;flex-direction:column;gap:6px}
.musica{display:flex;flex-direction:column;gap:10px}
.musica .row{grid-template-columns:minmax(0,1fr) minmax(0,1.3fr)}
@media (max-width:440px){.musica .row{grid-template-columns:1fr}}
.field select{appearance:none;-webkit-appearance:none;background:var(--bg) url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%278%27%3E%3Cpath d=%27M1 1l5 5 5-5%27 stroke=%27%239fbaa8%27 stroke-width=%272%27 fill=%27none%27/%3E%3C/svg%3E") no-repeat right 12px center;color:var(--fg);border:1px solid var(--line);border-radius:12px;padding:12px 32px 12px 12px;font:600 .95rem var(--body);width:100%;min-width:0}
.field select:focus-visible{outline:2px solid var(--accent2);outline-offset:1px}
.field input[type=text],.field input[type=tel]{background:var(--bg);color:var(--fg);border:1px solid var(--line);border-radius:12px;padding:12px;font:inherit;width:100%;box-sizing:border-box}
.pick{display:flex;align-items:center;gap:12px;background:var(--surface2);border-radius:14px;padding:10px 12px;min-width:0}
.pick .th{width:44px;height:44px;border-radius:10px;background:var(--bg) center/contain no-repeat;flex:none;display:grid;place-items:center;color:var(--muted);font-size:1.2rem}
.pick .tx{flex:1;min-width:0;font-size:.9rem}
.pick .tx div{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pick .tx small{color:var(--muted)}
.pick{flex-wrap:wrap}
.pick .tx{flex:1 1 150px}
.pick .acc{display:flex;gap:6px;flex-wrap:wrap;margin-left:auto}
.pick button{flex:none}
.mini{appearance:none;border:1px solid var(--line);background:transparent;color:var(--fg);border-radius:10px;padding:7px 10px;font:600 .8rem var(--body);cursor:pointer}
.stage{position:relative;border-radius:22px;overflow:hidden;background:#000;display:grid;place-items:center;max-height:62vh}
.stage canvas,.stage video{max-width:100%;max-height:62vh;display:block}
.bar{height:8px;border-radius:99px;background:var(--surface2);overflow:hidden}
.bar i{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .2s}
.note{font-size:.85rem;color:var(--muted);margin:0}
.msg{font-size:.9rem;border-radius:12px;padding:10px 12px;background:var(--surface2)}
.msg.err{color:var(--err)} .msg.ok{color:var(--ok)}
.list{display:flex;flex-direction:column;gap:6px;max-height:320px;overflow:auto}
.list div{display:flex;justify-content:space-between;gap:10px;background:var(--surface2);border-radius:10px;padding:8px 10px;font-size:.9rem}
.list span{color:var(--muted);font-variant-numeric:tabular-nums;white-space:nowrap}
.acepta{display:flex;gap:10px;align-items:flex-start;font-size:.82rem;color:var(--muted)}
.acepta input{width:20px;height:20px;flex:none;margin-top:1px;accent-color:var(--accent)}
.acepta a{color:var(--accent2)}
footer{text-align:center;font-size:.8rem;color:var(--muted)}
footer a{color:var(--muted)}
.listas{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.listas .lbl{margin-right:4px}
.listas .mini{border-color:var(--line);color:var(--fg)}
.listas .mini.on{border-color:var(--accent2);color:var(--accent2);background:#1f3a27}
.sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
@media (prefers-reduced-motion:reduce){.bar i{transition:none}}
</style>
