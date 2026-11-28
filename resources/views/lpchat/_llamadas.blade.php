{{-- 2026-11-28 (Jhoset): pantalla de llamada de voz / videollamada de LP Chat (como WhatsApp). La lógica está en _llamadas_js. --}}
<style>
.llam{position:fixed;inset:0;z-index:9000;display:flex;flex-direction:column;align-items:center;justify-content:space-between;color:#fff;
    background:radial-gradient(900px 500px at 50% -120px,rgba(166,243,74,.18),transparent 70%),#0b141a;padding:calc(48px + env(safe-area-inset-top,0px)) 20px calc(36px + env(safe-area-inset-bottom,0px));overflow:hidden}
.llam[hidden]{display:none}
.llam video{position:absolute;background:#000;object-fit:cover}
#ll-rem{inset:0;width:100%;height:100%;display:none}
#ll-loc{width:110px;height:150px;right:14px;top:calc(14px + env(safe-area-inset-top,0px));border-radius:14px;border:2px solid rgba(255,255,255,.35);display:none;z-index:2;transform:scaleX(-1)}
.llam.vid.loc #ll-loc{display:block}
.llam.vid.con #ll-rem{display:block}
.ll-info{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center}
.llam.vid.con .ll-info{align-items:flex-start;align-self:flex-start;text-shadow:0 1px 4px rgba(0,0,0,.7)}
.llam.vid.con .ll-av{display:none}
.ll-av{width:128px;height:128px;border-radius:50%;background:#26302a center/cover no-repeat;display:grid;place-items:center;font-size:3rem;font-weight:700;box-shadow:0 0 0 10px rgba(166,243,74,.08),0 0 0 22px rgba(166,243,74,.04)}
.llam.suena .ll-av{animation:llPulso 1.6s infinite}
@keyframes llPulso{0%{box-shadow:0 0 0 0 rgba(166,243,74,.45)}100%{box-shadow:0 0 0 34px rgba(166,243,74,0)}}
.ll-n{font-size:1.5rem;font-weight:700;max-width:86vw;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ll-s{font-size:.98rem;color:rgba(255,255,255,.75)}
.ll-cif{font-size:.78rem;color:rgba(255,255,255,.5)}
.ll-bts{position:relative;z-index:1;display:flex;gap:22px;justify-content:center;flex-wrap:wrap;background:rgba(0,0,0,.25);padding:14px 20px;border-radius:40px}
.ll-bt{display:flex;flex-direction:column;align-items:center;gap:6px;font-size:.75rem;color:rgba(255,255,255,.85)}
.ll-b{width:60px;height:60px;border-radius:50%;border:0;display:grid;place-items:center;background:rgba(255,255,255,.16);color:#fff;cursor:pointer}
.ll-b svg{width:28px;height:28px;fill:currentColor}
.ll-b.rojo{background:#ef4444}.ll-b.verde{background:#22c55e}.ll-b.off{background:#fff;color:#111}
.ll-b.rojo.colgar svg{transform:rotate(135deg)}
</style>
<div class="llam" id="llam" hidden>
    <video id="ll-rem" autoplay playsinline></video>
    <video id="ll-loc" autoplay playsinline muted></video>
    <audio id="ll-aud" autoplay></audio>
    <div class="ll-info">
        <div class="ll-av" id="ll-av"></div>
        <div class="ll-n" id="ll-n"></div>
        <div class="ll-s" id="ll-s"></div>
        <div class="ll-cif">🔒 Cifrada de extremo a extremo</div>
    </div>
    <div class="ll-bts" id="ll-bts"></div>
</div>
