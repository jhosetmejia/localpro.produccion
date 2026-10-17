<!DOCTYPE html>
<html lang="es">
<head>
    @include('curso._head')
    <title>Mis contactos · LocalPro</title>
    <style>
        .ci{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px dashed var(--line)}
        .ci:last-child{border-bottom:0}
        .ci .av{width:38px;height:38px;border-radius:50%;background:#e8f8ec;color:#11702f;display:grid;place-items:center;font-weight:900;flex:none}
        .ci .t{flex:1;min-width:0}.ci .t b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ci .t small{color:var(--mut);font-weight:600}
        .ci.hecho{opacity:.5}
        .mini{border:0;background:#f1f5f2;border-radius:999px;padding:.45rem .7rem;font-weight:700;font-size:.8rem;cursor:pointer;font-family:inherit}
        .num{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px}
        .num div{background:#fff;border:1.5px solid var(--line);border-radius:18px;padding:12px;text-align:center}
        .num b{display:block;font-size:1.5rem}.num small{color:var(--mut);font-weight:700;font-size:.78rem}
        .drop{position:relative;border:2px dashed #bfeccb;border-radius:18px;background:#f7fdf8;padding:18px;text-align:center;margin:10px 0}
        .drop input{position:absolute;inset:0;opacity:0;cursor:pointer}
        .bub{white-space:pre-wrap;background:#e7fbe9;border:1px solid #c9efcf;border-radius:14px 14px 14px 4px;padding:12px 14px;font-size:.92rem}
        details summary{cursor:pointer;font-weight:800}
        details ol{margin:8px 0 0 18px;padding:0}details li{margin-bottom:6px}
    </style>
</head>
<body>
<div class="w">
    <div class="top">
        <a href="{{ $volver ?: (auth()->user()->asesor?->curso_aprobado_at ? route('asesor.index') : route('curso.index')) }}" class="brand"><b>L</b>LocalPro · Mis contactos</a>
        @if ($volver)<a href="{{ $volver }}" class="btn btn-k" style="padding:.5rem 1rem;font-size:.9rem"><i class="fa-solid fa-arrow-left"></i> Volver al curso</a>@endif
    </div>
    @if (session('success'))<div class="msg ok">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="msg mal">{{ $errors->first() }}</div>@endif

    <div class="hero">
        <small>Su base de datos</small>
        <h1>Ofrezca LocalPro a <em>20 contactos</em> cada día</h1>
        <p>Suba los contactos de su celular y escríbales con un toque. El mensaje ya lleva su enlace de prueba gratis: si se inscriben, el negocio queda a su nombre.</p>
    </div>

    <div class="num">
        <div><b>{{ number_format($r['total'], 0, ',', '.') }}</b><small>contactos</small></div>
        <div><b>{{ $r['hoy'] }} / {{ \App\Http\Controllers\asesorContactosController::DIARIOS }}</b><small>escritos hoy</small></div>
        <div><b>{{ number_format($r['contactados'], 0, ',', '.') }}</b><small>en total</small></div>
    </div>

    <div class="card">
        <h2 style="margin:0 0 6px;font-size:1.15rem">1. Suba los contactos de su celular</h2>
        <details>
            <summary>¿Cómo saco los contactos de mi celular?</summary>
            <ol>
                <li><b>Android:</b> app Contactos → Arreglar y administrar (o Configuración) → Exportar a un archivo → Exportar. Queda un archivo .vcf en Descargas.</li>
                <li><b>Samsung:</b> Contactos → ☰ → Administrar contactos → Importar o exportar → Exportar → Almacenamiento interno.</li>
                <li><b>iPhone:</b> Contactos → Listas → mantenga presionado "Todos los contactos" → Exportar → Guardar en Archivos.</li>
                <li><b>Computador:</b> contacts.google.com → Exportar → CSV de Google.</li>
            </ol>
        </details>
        <form method="post" action="{{ route('asesor.contactos.subir') }}" enctype="multipart/form-data">
            @csrf
            <label class="drop" id="drop">
                <input type="file" name="archivo" accept=".vcf,.vcard,.csv,.xlsx,text/vcard" required onchange="document.getElementById('dropN').textContent=this.files.length?this.files[0].name:''">
                <i class="fa-solid fa-address-card" style="font-size:1.8rem;color:#16b34a"></i>
                <div style="font-weight:800;margin-top:6px">Toque aquí y elija el archivo de contactos</div>
                <small style="color:var(--mut)">.vcf del celular o .csv · no se repiten</small>
                <div id="dropN" style="font-weight:800;color:#11702f;margin-top:4px"></div>
            </label>
            <button class="btn btn-g btn-block"><i class="fa-solid fa-upload"></i> Subir mis contactos</button>
        </form>
    </div>

    <div class="card">
        <h2 style="margin:0 0 10px;font-size:1.15rem">2. Escríbales hoy</h2>
        @forelse ($lista as $c)
        <div class="ci" data-id="{{ $c->id }}" data-wa="{{ ($d = preg_replace('/\D/', '', $c->telefono)) && strlen($d) === 10 && str_starts_with($d, '3') ? '57' . $d : $d }}" data-nombre="{{ preg_match('/^contacto\s/i', $c->nombre) ? '' : \Illuminate\Support\Str::of($c->nombre)->explode(' ')->first() }}">
            <div class="av">{{ mb_strtoupper(mb_substr($c->nombre, 0, 1)) }}</div>
            <div class="t"><b>{{ $c->nombre }}</b><small>{{ $c->telefono }}</small></div>
            <button type="button" class="btn btn-w" style="padding:.55rem 1rem" data-enviar><i class="fa-brands fa-whatsapp"></i> Enviar</button>
            <button type="button" class="mini" data-marcar="numero_malo" title="Número malo">📵</button>
        </div>
        @empty
            @if ($r['total'] === 0)
            <p style="margin:0;color:var(--mut);font-weight:700">Primero suba sus contactos (paso 1).</p>
            @elseif ($r['hoy'] >= \App\Http\Controllers\asesorContactosController::DIARIOS)
            <div class="msg ok" style="margin:0">🎉 ¡Terminó sus {{ \App\Http\Controllers\asesorContactosController::DIARIOS }} contactos de hoy! Mañana le salen otros.</div>
            @else
            <div class="msg ok" style="margin:0">Ya escribió a todos sus contactos. Suba más para seguir creciendo.</div>
            @endif
        @endforelse
        @if ($hechosHoy->isNotEmpty())
        <details style="margin-top:12px"><summary>Escritos hoy ({{ $hechosHoy->count() }})</summary>
            @foreach ($hechosHoy as $c)<div class="ci hecho"><div class="av">✓</div><div class="t"><b>{{ $c->nombre }}</b><small>{{ $c->telefono }} · {{ $c->estado === 'enviado' ? 'Enviado' : 'Número malo' }}</small></div></div>@endforeach
        </details>
        @endif
    </div>

    <div class="card">
        <h2 style="margin:0 0 8px;font-size:1.15rem">Mensaje que se envía</h2>
        <div class="bub" id="vista"></div>
        <small style="color:var(--mut);display:block;margin-top:8px">Escriba en horario de 9 a. m. a 7 p. m. Si alguien le pide no escribirle más, respételo.</small>
    </div>
</div>
<script src="{{ asset('js/wa-limpio.js') }}?v=2"></script>
<script>
(function () {
    var MSG = @json($mensaje), ASESOR = @json(\Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first()), ENLACE = @json($yo->enlace);
    var URL = @json(url('/asesor/contactos/__ID__/marcar')), TOKEN = @json(csrf_token());
    function armar(n) { return MSG.replace('{nombre}', n || '').replace('¡Hola !', '¡Hola!').replace('{asesor}', ASESOR).replace('{enlace}', ENLACE); }
    document.getElementById('vista').textContent = armar('María');
    function marcar(it, estado) {
        it.classList.add('hecho');
        fetch(URL.replace('__ID__', it.dataset.id), { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' }, body: JSON.stringify({ estado: estado }) });
    }
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-enviar]'), m = e.target.closest('[data-marcar]');
        if (b) { var it = b.closest('.ci'); window.open('https://wa.me/' + it.dataset.wa + '?text=' + encodeURIComponent(armar(it.dataset.nombre)), '_blank'); if (!it.classList.contains('hecho')) marcar(it, 'enviado'); }
        if (m) marcar(m.closest('.ci'), m.dataset.marcar);
    });
})();
</script>
</body>
</html>
