@extends('layouts.app')

@section('title', 'Mis contactos')

@push('css')
<style>
.ac-num{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem;margin-bottom:1rem}
.ac-num div{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.8rem 1rem}
.ac-num div.v{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.ac-num b{display:block;font-size:1.6rem;font-weight:900;line-height:1}
.ac-num .v b{color:#a6f34a}
.ac-num small{font-weight:700;color:#6b7570}.ac-num .v small{color:#b8c4bc}
.ci{display:flex;align-items:center;gap:.7rem;padding:.6rem .2rem;border-bottom:1px solid #eef2ef}
.ci:last-child{border-bottom:0}
.ci .av{width:40px;height:40px;border-radius:50%;background:#dcfce7;color:#11702f;display:grid;place-items:center;font-weight:900;flex:none}
.ci .t{flex:1;min-width:0}
.ci .t b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ci .t small{color:#6b7570;font-weight:600}
.ci.hecho .av,.ci.hecho .t{opacity:.55}
.ci-wa{background:#25d366;color:#fff;border:0;border-radius:999px;padding:.55rem 1rem;font-weight:800;white-space:nowrap;display:flex;align-items:center;gap:.4rem}
.ci-wa:hover{background:#1ebe5b}
.ci-mini{border:0;background:#f1f5f2;border-radius:999px;width:36px;height:36px;display:grid;place-items:center;flex:none}
.ac-msg{white-space:pre-wrap;background:#e7fbe9;border:1px solid #c9efcf;border-radius:14px 14px 14px 4px;padding:.8rem 1rem;font-size:.92rem;line-height:1.45}
.ac-elige{display:flex;flex-wrap:wrap;gap:.4rem;align-items:center}
.ac-op{border:1.5px solid #d6dde8;background:#fff;border-radius:999px;padding:.35rem .9rem;font-weight:800;font-size:.85rem}
.ac-op.on{background:#0b0d0c;border-color:#0b0d0c;color:#a6f34a}
.ac-toast{position:fixed;left:50%;bottom:22px;transform:translateX(-50%);background:#b0173f;color:#fff;border-radius:16px;padding:.7rem 1.1rem;font-weight:700;z-index:2000;max-width:92vw;box-shadow:0 12px 30px rgba(0,0,0,.25)}
@media (max-width:480px){.ci-wa{padding:.5rem .8rem}}
</style>
@endpush

@section('content')
@php $diarios = \App\Http\Controllers\asesorContactosController::DIARIOS; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-brands fa-whatsapp me-1"></i>Su base de datos</small><h1>Ofrezca LocalPro a <span>{{ $diarios }} contactos</span> cada día</h1>
            <span class="text-muted fw-semibold">Suba los contactos de su celular y escríbales con un toque. El mensaje ya lleva su enlace de prueba gratis: si se inscriben, el negocio queda a su nombre.</span></div>
        <div class="lp-quick">
            @if ($volver)<a href="{{ $volver }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Volver al curso</a>
            @elseif (\Illuminate\Support\Facades\Route::has('asesor.vender') && $yo->curso_aprobado_at)<a href="{{ route('asesor.vender') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-rocket me-1"></i>Empiece a vender</a>@endif
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="ac-num">
        <div class="v"><b>{{ $r['hoy'] }} / {{ $diarios }}</b><small>escritos hoy</small></div>
        <div><b>{{ number_format($r['total'], 0, ',', '.') }}</b><small>contactos en su base</small></div>
        <div><b>{{ number_format($r['contactados'], 0, ',', '.') }}</b><small>escritos en total</small></div>
    </div>

    @foreach ($compartidas as $b)
    @php $t = $b['tienda']; $hechosT = $b['lista']->where('hecho', true)->count(); @endphp
    <div class="lp-box mb-3 ac-tienda" data-tienda="{{ $t->id }}" data-msg="{{ $b['mensaje'] }}" data-nombre-tienda="{{ $t->nombre }}" data-catalogo="{{ $b['catalogo'] }}" style="border-color:#c7d7fe;background:linear-gradient(180deg,#f5f8ff,#fff 40%)">
        <h3><span>👥 Base de datos de {{ $t->nombre }}</span><small class="text-muted">{{ $hechosT }} / {{ $b['lista']->count() }}</small></h3>
        <p class="text-muted fw-semibold small" style="margin-top:-.6rem">{{ $t->nombre }} le comparte sus contactos. A cada persona se le escribe <b>una sola vez</b>: los que ya contactó el negocio u otro asesor no le salen.</p>
        <div class="ac-elige mb-2">
            <span class="small fw-bold me-1">¿Qué mensaje envía?</span>
            <button type="button" class="ac-op on" data-op="tienda">Mensaje de {{ \Illuminate\Support\Str::limit($t->nombre, 22) }}</button>
            <button type="button" class="ac-op" data-op="localpro">Mensaje de LocalPro</button>
        </div>
        <details class="mb-2"><summary class="small fw-bold">Ver el mensaje</summary><div class="ac-msg mt-2" data-vista></div></details>
        @foreach ($b['lista'] as $c)
        <div class="ci {{ $c['hecho'] ? 'hecho' : '' }}" data-tienda-id="{{ $t->id }}" data-id="{{ $c['id'] }}" data-wa="{{ $c['wa'] }}" data-nombre="{{ $c['primer'] }}">
            <div class="av">{{ $c['hecho'] ? '✓' : mb_strtoupper(mb_substr($c['nombre'], 0, 1)) }}</div>
            <div class="t"><b>{{ $c['nombre'] }}</b><small>{{ $c['telefono'] }}@if ($c['estado']) · {{ \App\Models\RedContacto::ESTADOS[$c['estado']] ?? '' }}@endif @if ($c['otro']) · ya le escribió otra persona @endif</small></div>
            @if (!$c['hecho'])
            <button type="button" class="ci-wa" data-enviar><i class="fa-brands fa-whatsapp"></i> Enviar</button>
            <button type="button" class="ci-mini" data-marcar="numero_malo" title="Número malo">📵</button>
            @endif
        </div>
        @endforeach
    </div>
    @endforeach

    @if ($redLista->isNotEmpty())
    @php $redHechos = $redLista->whereNotNull('contactado_at')->count(); @endphp
    <div class="lp-box mb-3" style="border-color:#bfeccb;background:linear-gradient(180deg,#f4fcf6,#fff 40%)">
        <h3><span>⭐ Contactos de LocalPro para hoy</span><small class="text-muted">{{ $redHechos }} / {{ $redLista->count() }}</small></h3>
        <p class="text-muted fw-semibold small" style="margin-top:-.6rem">Se los da LocalPro. A cada persona se le escribe <b>una sola vez</b>: cuando usted le escribe, ya no le sale a ningún otro asesor.</p>
        @foreach ($redLista as $c)
        @php $d = preg_replace('/\D/', '', $c->telefono); @endphp
        <div class="ci {{ $c->contactado_at ? 'hecho' : '' }}" data-red="1" data-id="{{ $c->id }}" data-wa="{{ strlen($d) === 10 && str_starts_with($d, '3') ? '57' . $d : $d }}" data-nombre="{{ preg_match('/^contacto\s/i', $c->nombre) ? '' : \Illuminate\Support\Str::of($c->nombre)->explode(' ')->first() }}">
            <div class="av">{{ $c->contactado_at ? '✓' : mb_strtoupper(mb_substr($c->nombre, 0, 1)) }}</div>
            <div class="t"><b>{{ $c->nombre }}</b><small>{{ $c->telefono }}@if ($c->estado) · {{ \App\Models\RedContacto::ESTADOS[$c->estado] ?? '' }}@endif</small></div>
            @if (!$c->contactado_at)
            <button type="button" class="ci-wa" data-enviar><i class="fa-brands fa-whatsapp"></i> Enviar</button>
            <button type="button" class="ci-mini" data-marcar="numero_malo" title="Número malo">📵</button>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div class="lp-box mb-3">
        <h3><span><i class="fa-brands fa-whatsapp me-1" style="color:#25d366"></i> Escríbales hoy · Mi base de datos</span><small class="text-muted">Solo los ve usted</small></h3>
        @forelse ($lista as $c)
        @php $d = preg_replace('/\D/', '', $c->telefono); @endphp
        <div class="ci" data-id="{{ $c->id }}" data-wa="{{ strlen($d) === 10 && str_starts_with($d, '3') ? '57' . $d : $d }}" data-nombre="{{ preg_match('/^contacto\s/i', $c->nombre) ? '' : \Illuminate\Support\Str::of($c->nombre)->explode(' ')->first() }}">
            <div class="av">{{ mb_strtoupper(mb_substr($c->nombre, 0, 1)) }}</div>
            <div class="t"><b>{{ $c->nombre }}</b><small>{{ $c->telefono }}</small></div>
            <button type="button" class="ci-wa" data-enviar><i class="fa-brands fa-whatsapp"></i> Enviar</button>
            <button type="button" class="ci-mini" data-marcar="numero_malo" title="Número malo">📵</button>
        </div>
        @empty
            @if ($r['total'] === 0)
            <p class="text-muted fw-bold mb-0">Primero suba los contactos de su celular aquí abajo.</p>
            @elseif ($r['hoy'] >= $diarios)
            <div class="alert alert-success fw-bold mb-0">🎉 ¡Terminó sus {{ $diarios }} contactos de hoy! Mañana le salen otros.</div>
            @else
            <div class="alert alert-success fw-bold mb-0">Ya escribió a todos sus contactos. Suba más para seguir creciendo.</div>
            @endif
        @endforelse
        @if ($hechosHoy->isNotEmpty())
        <details class="mt-2"><summary class="fw-bold">Escritos hoy ({{ $hechosHoy->count() }})</summary>
            @foreach ($hechosHoy as $c)<div class="ci hecho"><div class="av">✓</div><div class="t"><b>{{ $c->nombre }}</b><small>{{ $c->telefono }} · {{ $c->estado === 'enviado' ? 'Enviado' : 'Número malo' }}</small></div></div>@endforeach
        </details>
        @endif
    </div>

    @include('cliente._contactos-celular', [
        'accion' => route('asesor.contactos.subir'),
        'texto' => 'LocalPro saca de cada contacto el <b>nombre y el celular</b> y los guarda en su base, sin repetir. Cada día le salen ' . $diarios . ' para escribirles.',
        'boton' => 'Subir mis contactos',
        'icono' => 'fa-upload',
        'extra' => 'asesor._contactos-mensaje',
    ])
</div>
@endsection

@push('js')
<script src="{{ asset('js/wa-limpio.js') }}?v=2"></script>
<script>
(function () {
    var MSG = @json($mensaje), ASESOR = @json(\Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first()), ENLACE = @json($yo->enlace);
    var URL = @json(url('/asesor/contactos/__ID__/marcar')), URL_RED = @json(url('/asesor/contactos/red/__ID__/marcar')), TOKEN = @json(csrf_token());
    function armar(n) { return MSG.replace('{nombre}', n || '').replace('¡Hola !', '¡Hola!').replace('{asesor}', ASESOR).replace('{enlace}', ENLACE); }
    var URL_T = @json(url('/asesor/contactos/tienda/__T__/__ID__'));
    // Bases compartidas por negocios: el asesor elige si manda el mensaje del negocio o el de LocalPro
    function opcion(box) { var b = box.querySelector('.ac-op.on'); return b ? b.dataset.op : 'tienda'; }
    function armarTienda(box, n) {
        if (opcion(box) === 'localpro') return armar(n);
        return box.dataset.msg.replace(/\{nombre\}/g, n || '').replace(/¡Hola\s+!/, '¡Hola!').replace(/Hola\s+,/, 'Hola,')
            .replace(/\{tienda\}/g, box.dataset.nombreTienda).replace(/\{catalogo\}/g, box.dataset.catalogo || '');
    }
    function guardarOp(box) { try { localStorage.setItem('lp_msg_tienda_' + box.dataset.tienda, opcion(box)); } catch (e) {} }
    document.querySelectorAll('.ac-tienda').forEach(function (box) {
        var antes = null; try { antes = localStorage.getItem('lp_msg_tienda_' + box.dataset.tienda); } catch (e) {}
        if (antes) box.querySelectorAll('.ac-op').forEach(function (b) { b.classList.toggle('on', b.dataset.op === antes); });
        var v = box.querySelector('[data-vista]'); if (v) v.textContent = armarTienda(box, 'María');
        box.addEventListener('click', function (e) {
            var op = e.target.closest('.ac-op'); if (!op) return;
            box.querySelectorAll('.ac-op').forEach(function (b) { b.classList.toggle('on', b === op); });
            guardarOp(box); if (v) v.textContent = armarTienda(box, 'María');
        });
    });
    function urlTienda(it, fin) { return URL_T.replace('__T__', it.dataset.tiendaId).replace('__ID__', it.dataset.id) + (fin || ''); }
    function hechoTienda(it) { it.classList.add('hecho'); it.querySelectorAll('[data-enviar],[data-marcar]').forEach(function (x) { x.remove(); }); }
    function marcarTienda(it, estado) {
        hechoTienda(it);
        return fetch(urlTienda(it, '/marcar'), { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' }, body: JSON.stringify({ estado: estado }) })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (d) { if (d && d.ok === false && d.mensaje) aviso(d.mensaje); });
    }
    var vista = document.getElementById('acVista'); if (vista) vista.textContent = armar('María');
    function aviso(m) {
        var c = document.createElement('div'); c.className = 'ac-toast'; c.textContent = m;
        document.body.appendChild(c); setTimeout(function () { c.remove(); }, 5000);
    }
    function marcar(it, estado) {
        it.classList.add('hecho');
        // Una sola vez: se quitan los botones para que no se le vuelva a escribir
        it.querySelectorAll('[data-enviar],[data-marcar]').forEach(function (x) { x.remove(); });
        var u = (it.dataset.red ? URL_RED : URL).replace('__ID__', it.dataset.id);
        return fetch(u, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' }, body: JSON.stringify({ estado: estado }) })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (d) { if (d && d.ok === false && d.mensaje) aviso(d.mensaje); });
    }
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-enviar]'), m = e.target.closest('[data-marcar]');
        var itT = (b || m) ? (b || m).closest('.ci[data-tienda-id]') : null;
        if (itT) {
            if (itT.classList.contains('hecho')) return;
            if (m) { marcarTienda(itT, m.dataset.marcar); return; }
            var ventana = window.open('', '_blank'); // se abre ya para que el navegador no la bloquee
            // Antes de escribir se revisa que nadie le haya escrito ya a ese número (el negocio u otro asesor)
            fetch(urlTienda(itT), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
                if (d.libre === false) { if (ventana) ventana.close(); hechoTienda(itT); aviso(d.mensaje); return; }
                var url = 'https://wa.me/' + itT.dataset.wa + '?text=' + encodeURIComponent(armarTienda(itT.closest('.ac-tienda'), itT.dataset.nombre));
                if (ventana) ventana.location.href = url; else window.open(url, '_blank');
                marcarTienda(itT, 'enviado');
            }).catch(function () { if (ventana) ventana.close(); aviso('No se pudo revisar el contacto. Revise la conexión.'); });
            return;
        }
        if (b) { var it = b.closest('.ci'); if (it.classList.contains('hecho')) return; window.open('https://wa.me/' + it.dataset.wa + '?text=' + encodeURIComponent(armar(it.dataset.nombre)), '_blank'); marcar(it, 'enviado'); }
        if (m) marcar(m.closest('.ci'), m.dataset.marcar);
    });
})();
</script>
@endpush
