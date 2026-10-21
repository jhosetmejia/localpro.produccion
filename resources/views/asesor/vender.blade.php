@extends('layouts.app')

@section('title', 'Empiece a vender LocalPro')

@push('css')
<style>
.ev-hero{background:#0b0d0c;color:#fff;border-radius:28px;padding:1.8rem 1.6rem;position:relative;overflow:hidden;margin-bottom:1rem}
.ev-hero::after{content:"";position:absolute;width:320px;height:320px;border-radius:50%;border:46px solid rgba(166,243,74,.12);right:-100px;top:-120px}
.ev-hero small{color:#9fe3b0;font-weight:800;letter-spacing:.1em;text-transform:uppercase;font-size:.75rem}
.ev-hero h1{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:900;line-height:1.08;margin:.35rem 0 .5rem;max-width:22ch;position:relative;z-index:1}
.ev-hero h1 em{font-style:normal;color:#a6f34a}
.ev-hero p{color:#c9d3cc;max-width:56ch;font-weight:500;margin:0;position:relative;z-index:1}
.ev-num{display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1.1rem;position:relative;z-index:1}
.ev-num div{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:.55rem .9rem}
.ev-num b{display:block;font-size:1.35rem;font-weight:900;color:#a6f34a;line-height:1.1}
.ev-num span{font-size:.75rem;color:#b8c4bc;font-weight:700}
.ev-paso{background:#fff;border:1.5px solid #e3ebe6;border-radius:24px;padding:1.15rem 1.2rem;margin-bottom:.8rem;display:flex;gap:1rem;align-items:flex-start}
.ev-paso.hecho{border-color:#bfeccb;background:linear-gradient(180deg,#f4fcf6,#fff)}
.ev-paso .n{width:42px;height:42px;border-radius:50%;background:#0b0d0c;color:#a6f34a;display:grid;place-items:center;font-weight:900;font-size:1.1rem;flex:none}
.ev-paso.hecho .n{background:#16b34a;color:#fff}
.ev-paso h3{font-size:1.1rem;font-weight:900;margin:0 0 .25rem}
.ev-paso p{color:#55605a;font-weight:500;margin:0 0 .7rem}
.ev-paso .cuerpo{flex:1;min-width:0}
.ev-barra{height:10px;border-radius:99px;background:#e3ebe6;overflow:hidden;margin:.2rem 0 .7rem;max-width:420px}
.ev-barra i{display:block;height:100%;background:linear-gradient(90deg,#a3f04a,#16b84e)}
.ev-msg{white-space:pre-wrap;background:#e7fbe9;border:1px solid #c9efcf;border-radius:14px 14px 14px 4px;padding:.8rem 1rem;font-size:.9rem;line-height:1.45;margin-bottom:.6rem;max-height:260px;overflow:auto}
.ev-btns{display:flex;gap:.5rem;flex-wrap:wrap}
.ev-btns .btn{border-radius:999px;font-weight:800}
.ev-tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.6rem}
.ev-tabs button{border:1.5px solid #e3ebe6;background:#fff;border-radius:999px;padding:.4rem .85rem;font-weight:800;font-size:.85rem}
.ev-tabs button.on{background:#0b0d0c;color:#fff;border-color:#0b0d0c}
.ev-toast{position:fixed;left:50%;bottom:22px;transform:translateX(-50%) translateY(20px);background:#0b0d0c;color:#fff;border-radius:999px;padding:.6rem 1.2rem;font-weight:800;opacity:0;transition:.25s;z-index:2000;pointer-events:none}
.ev-toast.on{opacity:1;transform:translateX(-50%) translateY(0)}
@media (max-width:560px){.ev-paso{flex-direction:column;gap:.6rem}}
</style>
@endpush

@section('content')
@php $pctHoy = min(100, round($hoy / max(1, $diarios) * 100)); @endphp
<div class="lp-dash">
    <div class="ev-hero">
        <small><i class="fa-solid fa-rocket me-1"></i>Empiece a vender LocalPro</small>
        <h1>Le ayudamos a <em>vender LocalPro</em> desde hoy</h1>
        <p>Siga estos pasos todos los días. Cada mensaje lleva su enlace con su código <b>{{ $yo->codigo }}</b>: el negocio que se registre queda a su nombre y arranca con 2 meses gratis.</p>
        <div class="ev-num">
            <div><b>{{ $hoy }} / {{ $diarios }}</b><span>mensajes hoy</span></div>
            <div><b>{{ number_format($base, 0, ',', '.') }}</b><span>contactos en su base</span></div>
            <div><b>{{ $negociosMes }}</b><span>negocios este mes</span></div>
            <div><b>{{ $negocios }}</b><span>negocios en total</span></div>
        </div>
    </div>

    <div class="ev-paso {{ $base ? 'hecho' : '' }}">
        <span class="n">{{ $base ? '✓' : '1' }}</span>
        <div class="cuerpo">
            <h3>Importe su base de datos</h3>
            <p>Suba los contactos de su celular (.vcf) o un Excel. LocalPro los organiza sin repetir y cada día le da {{ $diarios }} para escribirles. Además, LocalPro le reparte contactos de negocios para hoy.</p>
            <div class="ev-btns">
                <a href="{{ route('asesor.contactos') }}" class="btn btn-dark"><i class="fa-solid fa-address-book me-1"></i>{{ $base ? 'Subir más contactos' : 'Importar mis contactos' }}</a>
            </div>
        </div>
    </div>

    <div class="ev-paso {{ $hoy >= $diarios ? 'hecho' : '' }}">
        <span class="n">{{ $hoy >= $diarios ? '✓' : '2' }}</span>
        <div class="cuerpo">
            <h3>Mande {{ $diarios }} mensajes de WhatsApp hoy</h3>
            <p>Con un toque se abre WhatsApp con el mensaje listo y su enlace. Poquitos y todos los días: eso es lo que trae negocios.</p>
            <div class="ev-barra"><i style="width:{{ $pctHoy }}%"></i></div>
            <div class="ev-msg" data-plantilla="directo"></div>
            <div class="ev-btns">
                <a href="{{ route('asesor.contactos') }}" class="btn btn-success"><i class="fa-brands fa-whatsapp me-1"></i>{{ $hoy ? 'Seguir escribiendo' : 'Empezar mis ' . $diarios . ' mensajes' }}</a>
                <button type="button" class="btn btn-light" data-copiar="directo"><i class="fa-regular fa-copy me-1"></i>Copiar mensaje</button>
            </div>
        </div>
    </div>

    <div class="ev-paso" id="pasoGrupos">
        <span class="n">3</span>
        <div class="cuerpo">
            <h3>Comparta la propuesta en grupos de Facebook y WhatsApp</h3>
            <p>Publique en grupos de emprendedores, comerciantes y ventas de su ciudad. Copie el texto, abra el grupo y péguelo (mejor con una foto o un video de LocalPro).</p>
            <div class="ev-msg" data-plantilla="grupo"></div>
            <div class="ev-btns">
                <button type="button" class="btn btn-dark" data-copiar="grupo" data-marca="grupos"><i class="fa-regular fa-copy me-1"></i>Copiar publicación</button>
                <a class="btn btn-success" target="_blank" rel="noopener" data-compartir="grupo" data-marca="grupos"><i class="fa-brands fa-whatsapp me-1"></i>Enviar a un grupo de WhatsApp</a>
                <a class="btn btn-primary" style="background:#1877f2;border-color:#1877f2" target="_blank" rel="noopener" href="https://www.facebook.com/groups/feed/" data-copiar-abrir="grupo" data-marca="grupos"><i class="fa-brands fa-facebook me-1"></i>Copiar y abrir mis grupos de Facebook</a>
            </div>
            <small class="text-muted d-block mt-2">💡 Busque en Facebook "emprendedores {{ $yo->ciudad ?: 'su ciudad' }}", "comerciantes {{ $yo->ciudad ?: 'su ciudad' }}" o "ventas {{ $yo->ciudad ?: 'su ciudad' }}". Publique en 3 grupos distintos al día y responda rápido a quien comente.</small>
        </div>
    </div>

    <div class="ev-paso" id="pasoEstado">
        <span class="n">4</span>
        <div class="cuerpo">
            <h3>Publíquelo en sus estados de WhatsApp</h3>
            <p>Un estado al día con su enlace. Sus contactos lo ven sin que usted les escriba.</p>
            <div class="ev-msg" data-plantilla="estado"></div>
            <div class="ev-btns">
                <button type="button" class="btn btn-dark" data-copiar="estado" data-marca="estado"><i class="fa-regular fa-copy me-1"></i>Copiar texto del estado</button>
                <a class="btn btn-success" target="_blank" rel="noopener" data-compartir="estado" data-marca="estado"><i class="fa-brands fa-whatsapp me-1"></i>Compartir en WhatsApp</a>
            </div>
        </div>
    </div>

    <div class="ev-paso">
        <span class="n">5</span>
        <div class="cuerpo">
            <h3>Visite 5 negocios y haga 1 demostración</h3>
            <p>Lo que más vende es mostrarlo en persona: caja, panel con la ganancia del día y tienda en línea. Inscríbalo ahí mismo con su enlace.</p>
            <div class="ev-btns">
                <a href="{{ route('aprende.curso', 'asesor-presentar') }}" class="btn btn-light" style="border:1.5px solid #bfeccb"><i class="fa-solid fa-graduation-cap me-1"></i>Curso: presentar en 5 minutos</a>
                <button type="button" class="btn btn-light" style="border:1.5px solid #bfeccb" data-copiar-texto="{{ $enlace }}"><i class="fa-solid fa-link me-1"></i>Copiar mi enlace</button>
            </div>
        </div>
    </div>
</div>
<div class="ev-toast" id="evToast"></div>
@endsection

@push('js')
<script>
(function () {
    var M = @json(collect($mensajes)->map(fn ($m) => $m['texto'])), ENLACE = @json($enlace);
    function texto(k, nombre) { return M[k].replace(/\{enlace\}/g, ENLACE).replace(/\{nombre\}/g, nombre || '').replace('¡Hola !', '¡Hola!'); }
    function aviso(t) { var e = document.getElementById('evToast'); e.textContent = t; e.classList.add('on'); setTimeout(function () { e.classList.remove('on'); }, 2200); }
    function copiar(t, ok) {
        var viejo = function () { var a = document.createElement('textarea'); a.value = t; document.body.appendChild(a); a.select(); try { document.execCommand('copy'); aviso(ok); } catch (e) { aviso('No se pudo copiar'); } a.remove(); };
        if (navigator.clipboard) navigator.clipboard.writeText(t).then(function () { aviso(ok); }, viejo); else viejo();
    }
    // Marca de "hecho hoy" para los pasos que se hacen fuera de LocalPro (solo en este celular)
    var HOY = new Date().toISOString().slice(0, 10);
    function marcar(k) { try { localStorage.setItem('ev_' + k, HOY); } catch (e) {} pintar(); }
    function pintar() {
        [['grupos', 'pasoGrupos'], ['estado', 'pasoEstado']].forEach(function (x) {
            var v = null; try { v = localStorage.getItem('ev_' + x[0]); } catch (e) {}
            var el = document.getElementById(x[1]); if (!el) return;
            var ok = v === HOY; el.classList.toggle('hecho', ok);
            el.querySelector('.n').textContent = ok ? '✓' : (x[0] === 'grupos' ? '3' : '4');
        });
    }
    document.querySelectorAll('[data-plantilla]').forEach(function (e) { e.textContent = texto(e.dataset.plantilla, 'María'); });
    document.querySelectorAll('[data-compartir]').forEach(function (a) { a.href = 'https://wa.me/?text=' + encodeURIComponent(texto(a.dataset.compartir)); });
    document.addEventListener('click', function (e) {
        var c = e.target.closest('[data-copiar]'), ct = e.target.closest('[data-copiar-texto]'), ca = e.target.closest('[data-copiar-abrir]'), sh = e.target.closest('[data-compartir]');
        if (c) copiar(texto(c.dataset.copiar), '✅ Mensaje copiado');
        if (ct) copiar(ct.dataset.copiarTexto, '✅ Enlace copiado');
        if (ca) copiar(texto(ca.dataset.copiarAbrir), '✅ Copiado: péguelo en el grupo');
        var m = e.target.closest('[data-marca]'); if (m) marcar(m.dataset.marca);
    });
    pintar();
})();
</script>
@endpush
