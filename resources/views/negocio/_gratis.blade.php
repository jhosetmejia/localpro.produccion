{{-- LocalPro gratis: 1 mes por cada emprendedor que se une con el link y cumple los pasos. Recibe $tienda y $rm (ReferidosMes::revisar) --}}
@php
    $enlaceG = \App\Support\Referidos::enlace($tienda);
    $msgG = \App\Support\ReferidosMes::mensaje($tienda);
    $invG = collect($rm['invitados'] ?? []);
    $ganG = $invG->filter(fn ($x) => $x['ganado'])->count();
    $caminoG = $invG->filter(fn ($x) => !$x['ganado'])->count();
    $maxG = \App\Support\ReferidosMes::maximoAnual();
@endphp
<style>
.lg{background:#0b0d0c;color:#fff;border-radius:28px;padding:24px 22px;position:relative;overflow:hidden;margin:16px 0}
.lg::before{content:"";position:absolute;inset:0;background:radial-gradient(60% 60% at 0% 0%,rgba(47,209,90,.35),transparent 70%),radial-gradient(50% 60% at 100% 100%,rgba(166,243,74,.18),transparent 70%);pointer-events:none}
.lg>*{position:relative}
.lg-tag{display:inline-block;background:linear-gradient(135deg,#a6f34a,#2fd15a);color:#0b0d0c;font-weight:900;font-size:.75rem;border-radius:99px;padding:.3rem .75rem;letter-spacing:.04em;text-transform:uppercase}
.lg h2{font-weight:900;font-size:clamp(1.4rem,4vw,1.9rem);line-height:1.15;margin:.6rem 0 .5rem}
.lg h2 span{color:#a6f34a}
.lg p{color:#c9d3cc;margin:0;line-height:1.5;max-width:640px}
.lg-slots{display:flex;gap:6px;flex-wrap:wrap;margin:16px 0 6px}
.lg-slots i{width:34px;height:34px;border-radius:11px;border:2px dashed #3a4a3f;display:grid;place-items:center;font-style:normal;font-weight:900;font-size:.85rem;color:#6f8276}
.lg-slots i.ok{background:linear-gradient(135deg,#a6f34a,#2fd15a);border:0;color:#0b0d0c}
.lg-slots i.va{border:2px solid #a6f34a;color:#a6f34a}
.lg-cuenta{font-weight:700;color:#c9d3cc;font-size:.9rem}
.lg-cuenta b{color:#a6f34a}
.lg-link{display:flex;gap:8px;flex-wrap:wrap;align-items:center;background:#161a18;border:1.5px solid #2a312d;border-radius:18px;padding:10px;margin-top:14px}
.lg-link code{flex:1 1 200px;min-width:0;color:#fff;font-weight:700;font-size:.92rem;word-break:break-all;background:none;padding:0 6px}
.lg-link a,.lg-link button{border:0;border-radius:99px;font-weight:800;font-size:.9rem;padding:.6rem 1rem;cursor:pointer;text-decoration:none;font-family:inherit;display:inline-flex;align-items:center;gap:.4rem}
.lg-wa{background:#25d366;color:#fff}
.lg-cp{background:#fff;color:#0b0d0c}
.lg-inv{margin-top:16px;display:flex;flex-direction:column;gap:8px}
.lg-i{background:#161a18;border-radius:16px;padding:10px 12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.lg-i .n{flex:1 1 160px;min-width:0}
.lg-i .n b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lg-i .n small{color:#9fb0a5;font-weight:600}
.lg-bar{height:6px;border-radius:99px;background:#2a312d;overflow:hidden;margin-top:6px}
.lg-bar span{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#2fd15a)}
.lg-chip{font-weight:800;font-size:.8rem;border-radius:99px;padding:.3rem .7rem;white-space:nowrap}
.lg-chip.ok{background:#a6f34a;color:#0b0d0c}
.lg-chip.va{background:#2a312d;color:#c9d3cc}
.lg details{margin-top:14px;color:#c9d3cc}
.lg summary{cursor:pointer;font-weight:800;color:#fff}
.lg details ol,.lg details ul{margin:.6rem 0 0;padding-left:1.2rem;line-height:1.55;font-size:.92rem}
</style>
<div class="lg" id="localpro-gratis">
    <span class="lg-tag"><i class="fa-solid fa-gift me-1"></i>LocalPro gratis</span>
    <h2>¿Quiere LocalPro <span>gratis</span> con todas sus funciones?</h2>
    <p>Comparta su link con <b style="color:#fff">10 emprendedores</b>. Por cada uno que se una y complete sus primeros pasos, usted gana <b style="color:#fff">1 mes gratis</b>. Ellos también empiezan con 2 meses gratis.</p>

    <div class="lg-slots" aria-hidden="true">
        @for ($k = 1; $k <= 10; $k++)
            <i class="{{ $k <= $ganG ? 'ok' : ($k <= $ganG + $caminoG ? 'va' : '') }}">{{ $k <= $ganG ? '✓' : $k }}</i>
        @endfor
    </div>
    <div class="lg-cuenta"><b>{{ $ganG }}</b> {{ $ganG === 1 ? 'mes ganado' : 'meses ganados' }}@if ($caminoG) · {{ $caminoG }} {{ $caminoG === 1 ? 'emprendedor en camino' : 'emprendedores en camino' }}@endif</div>

    <div class="lg-link">
        <code id="lgEnlace">{{ $enlaceG }}</code>
        <a class="lg-wa" href="https://wa.me/?text={{ rawurlencode($msgG) }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i>Compartir</a>
        <button type="button" class="lg-cp" data-copiar-gratis="{{ $enlaceG }}"><i class="fa-regular fa-copy"></i>Copiar</button>
    </div>

    @if ($invG->isNotEmpty())
    <div class="lg-inv">
        @foreach ($invG as $x)
            @php $falta = collect($x['reglas'])->first(fn ($r) => !$r['ok']); $pct = (int) round($x['hechas'] / max(1, $x['total']) * 100); @endphp
            <div class="lg-i">
                <div class="n"><b>{{ $x['tienda']->nombre }}</b>
                    <small>@if ($x['ganado'])Cumplió todo · {{ \Illuminate\Support\Carbon::parse($x['ganado']->created_at)->format('d/m/Y') }}@elseif ($falta)Le falta: {{ mb_strtolower($falta['txt']) }} ({{ $falta['det'] }})@else Listo, se suma hoy @endif</small>
                    @unless ($x['ganado'])<div class="lg-bar"><span style="width:{{ $pct }}%"></span></div>@endunless
                </div>
                @if ($x['ganado'])<span class="lg-chip ok">🎁 +1 mes</span>@else<span class="lg-chip va">{{ $x['hechas'] }}/{{ $x['total'] }}</span>@endif
            </div>
        @endforeach
    </div>
    @endif

    <details>
        <summary>Reglas para ganar el mes</summary>
        <ol>
            <li>El emprendedor se registra con <b>su link</b> (o su código {{ $tienda->codigo }}).</li>
            <li>Completa los <b>primeros pasos</b> de LocalPro: logo y WhatsApp, 3 productos con foto, tienda en línea activa, 10 clientes, 5 clientes contactados y su primera venta.</li>
            <li>Usa un <b>WhatsApp propio</b>: distinto al suyo y que no tenga otro negocio en LocalPro.</li>
            <li>Lleva al menos <b>{{ \App\Support\ReferidosMes::DIAS_MINIMOS }} días</b> registrado y ha usado LocalPro en <b>{{ \App\Support\ReferidosMes::DIAS_DE_USO }} días distintos</b>.</li>
            <li>Su cuenta está activa (no suspendida).</li>
        </ol>
        <ul>
            <li>Cuando cumple todo, el mes se suma solo a su plan. Si su plan ya venció, cuenta desde ese día y se desbloquea.</li>
            <li>Máximo {{ $maxG }} meses gratis por año. Un mes por cada emprendedor.</li>
            <li>Mientras sus invitados completan los pasos (30 días desde que se unen), su información no se borra.</li>
            <li>Las cuentas falsas o repetidas no cuentan y LocalProo puede anular los meses ganados con ellas. <a href="{{ route('legal.terminos') }}#localpro-gratis" target="_blank" style="color:#a6f34a">Condiciones</a></li>
        </ul>
    </details>
</div>
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copiar-gratis]'); if (!b) return;
    var t = b.dataset.copiarGratis, ok = function () { if (window.LP && LP.toast) LP.toast('Link copiado ✅', 'ok'); else b.textContent = 'Copiado ✅'; };
    if (navigator.clipboard) navigator.clipboard.writeText(t).then(ok).catch(function () {});
    else { var a = document.createElement('textarea'); a.value = t; document.body.appendChild(a); a.select(); try { document.execCommand('copy'); ok(); } catch (x) {} a.remove(); }
});
</script>
