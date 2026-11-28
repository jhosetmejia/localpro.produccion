{{-- 2026-11-28 (Jhoset): seleccionar mensajes como WhatsApp (mantener presionado): barra de arriba, reacciones, responder. Lógica en _seleccion_js. --}}
<style>
.selbar{position:absolute;left:0;right:0;top:0;height:60px;z-index:5;display:flex;align-items:center;gap:2px;padding:0 6px;background:var(--panel);border-bottom:1px solid var(--borde)}
@media (max-width:760px){.selbar{height:calc(58px + env(safe-area-inset-top,0px));padding-top:env(safe-area-inset-top,0px)}}
.selbar[hidden]{display:none}
.selbar .n{flex:1;font-size:1.15rem;font-weight:600;padding-left:8px}
.selbar .ib{color:var(--txt)}
.selbar .ib[hidden]{display:none}
.conv{position:relative}
.m.sel{box-shadow:0 0 0 2px var(--verde);}
.msgs .sel-fondo{background:rgba(166,243,74,.14)}
@media (hover:none){.msgs .m{-webkit-user-select:none;user-select:none;-webkit-touch-callout:none}}
.reacbar{position:fixed;z-index:60;display:flex;align-items:center;gap:2px;background:var(--panel);border:1px solid var(--borde);border-radius:30px;padding:5px 8px;box-shadow:0 8px 30px rgba(0,0,0,.45)}
.reacbar[hidden]{display:none}
.reacbar button{background:none;border:0;font-size:1.65rem;width:44px;height:44px;border-radius:50%;cursor:pointer;line-height:1}
.reacbar button.on{background:rgba(166,243,74,.2)}
.reacbar .mas{font-size:1.4rem;color:var(--txt2);background:rgba(255,255,255,.08)}
.m.con-reac{margin-bottom:16px}
.m .reac{position:absolute;bottom:-15px;display:flex;align-items:center;gap:1px;background:var(--panel2,var(--panel));border:1px solid var(--borde);border-radius:14px;padding:1px 6px;font-size:.92rem;cursor:pointer;line-height:1.35}
.m.el .reac{left:10px}.m.yo .reac{right:10px}
.m .reac small{font-size:.72rem;color:var(--txt2);margin-left:3px}
.m .cita{display:block;border-left:4px solid var(--verde);background:rgba(0,0,0,.18);border-radius:7px;padding:5px 8px;margin:2px -2px 5px;cursor:pointer;font-size:.84rem;white-space:normal}
.m.yo .cita{background:rgba(0,0,0,.12);border-left-color:#0b3d6b}
.m .cita b{display:block;font-size:.8rem;margin-bottom:1px}
.m .cita span{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;opacity:.85}
.m .fw{display:block;font-size:.76rem;font-style:italic;opacity:.7;margin-bottom:2px}
.m.brillo{animation:llBrillo 1.4s}
@keyframes llBrillo{0%,60%{box-shadow:0 0 0 3px var(--verde)}100%{box-shadow:none}}
.resp-prev{flex:none;display:flex;align-items:center;gap:6px;padding:6px 8px 0;background:var(--panel)}
.resp-prev[hidden]{display:none}
.resp-prev .cita{flex:1;min-width:0;border-left:4px solid var(--verde);background:var(--panel3);border-radius:8px;padding:6px 10px;font-size:.85rem}
.resp-prev .cita b{display:block;font-size:.8rem}
.resp-prev .cita span{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--txt2)}
.fw-lista{max-height:50vh;overflow-y:auto;margin-top:8px}
.fw-lista label{display:flex;align-items:center;gap:10px;padding:7px 4px;cursor:pointer;border-radius:10px}
.fw-lista label:hover{background:rgba(255,255,255,.05)}
.fw-lista input{width:20px;height:20px;accent-color:#22c55e}
.emo-todo{display:grid;grid-template-columns:repeat(auto-fill,minmax(40px,1fr));gap:2px;max-height:50vh;overflow-y:auto}
.emo-todo button{background:none;border:0;font-size:1.6rem;height:44px;cursor:pointer;border-radius:8px}
.emo-todo button:hover{background:rgba(255,255,255,.08)}
</style>
