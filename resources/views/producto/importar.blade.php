@extends('layouts.app')

@section('title', 'Importar productos')

@push('css')
<style>
.im-op{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
@media (max-width:860px){.im-op{grid-template-columns:minmax(0,1fr)}}
.im-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:24px;padding:1.4rem;display:flex;flex-direction:column;gap:1rem;min-width:0}
.im-card.yo{border-color:#bfeccb;background:linear-gradient(180deg,#f4fcf6,#fff 40%)}
.im-card h2{font-size:1.2rem;font-weight:900;margin:0;display:flex;align-items:center;gap:.6rem}
.im-card h2 .ic{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:#0b0d0c;color:#a6f34a;font-size:1.1rem;flex:none}
.im-card.yo h2 .ic{background:linear-gradient(135deg,#a6f34a,#16b34a);color:#0b0d0c}
.im-card > p{margin:0;color:#5b665e;font-weight:500}
.im-pasos{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.8rem}
.im-pasos li{display:flex;gap:.8rem;align-items:flex-start}
.im-pasos .n{width:30px;height:30px;border-radius:50%;background:#0b0d0c;color:#fff;font-weight:900;display:grid;place-items:center;flex:none;font-size:.9rem}
.im-pasos b{display:block}
.im-pasos small{color:#6b7570;font-weight:500}
.im-drop{position:relative;border:2px dashed #bfeccb;border-radius:20px;background:#f7fdf8;padding:1.4rem 1rem;text-align:center;cursor:pointer;transition:all .15s}
.im-drop:hover,.im-drop.sobre{border-color:#16b34a;background:#ecfaef}
.im-drop input{position:absolute;inset:0;opacity:0;cursor:pointer}
.im-drop i{font-size:2rem;color:#16b34a}
.im-drop b{display:block;margin-top:.4rem}
.im-drop small{color:#6b7570}
.im-drop .im-nom{display:none;margin-top:.5rem;font-weight:800;color:#11702f;word-break:break-all}
.im-drop.lleno .im-nom{display:block}
.im-btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border:0;border-radius:999px;padding:.8rem 1.3rem;font-weight:800;text-decoration:none;cursor:pointer}
.im-btn.g{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c}
.im-btn.n{background:#0b0d0c;color:#fff}
.im-btn.l{background:#f1f5f2;color:#0b0d0c}
.im-btn.w{background:#25d366;color:#fff}
.im-acc{display:flex;gap:.6rem;flex-wrap:wrap}
.im-acc .im-btn{flex:1 1 auto}
.im-cols{display:flex;flex-wrap:wrap;gap:.35rem}
.im-cols span{background:#f1f5f2;border-radius:8px;padding:.2rem .55rem;font-size:.78rem;font-weight:700;color:#33403a}
.im-cols span.req{background:#dcfce7;color:#14753a}
.im-hist li .est{font-size:.72rem;font-weight:800;border-radius:999px;padding:.15rem .55rem;white-space:nowrap}
.im-hist .est.pendiente{background:#fef3c7;color:#92600a}.im-hist .est.lista{background:#dcfce7;color:#14753a}.im-hist .est.rechazada{background:#fde2e8;color:#b0173f}
.im-err{background:#fff8ec;border:1px solid #fde4b0;border-radius:16px;padding:.8rem 1rem;margin-bottom:1rem}
.im-err ul{margin:.4rem 0 0;padding-left:1.1rem;max-height:220px;overflow:auto;font-size:.88rem}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-file-excel me-1"></i>Productos</small><h1>Importar <span>productos</span></h1>
            <span class="text-muted fw-semibold">¿Ya tiene sus productos en Excel o en otro sistema? No los cree uno por uno.</span></div>
        <div class="lp-quick"><a href="{{ route('productos.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Mis productos</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    @if (session('importacion_errores'))
    <div class="im-err">
        <b><i class="fa-solid fa-circle-info me-1" style="color:#c27a00"></i> {{ count(session('importacion_errores')) }} fila(s) no se cargaron:</b>
        <ul>@foreach (session('importacion_errores') as $e)<li>{{ $e }}</li>@endforeach</ul>
        <small class="text-muted">Corríjalas en el Excel y súbalo otra vez: lo que ya está no se duplica, solo se actualiza.</small>
    </div>
    @endif

    <div class="im-op">
        {{-- Opción 1: lo hace el negocio --}}
        <div class="im-card yo">
            <h2><span class="ic"><i class="fa-solid fa-bolt"></i></span> Lo hago yo en 2 minutos</h2>
            <p>Suba su Excel tal como lo tiene. El sistema reconoce las columnas solo, usted revisa y quedan listos para vender.</p>
            <ul class="im-pasos">
                <li><span class="n">1</span><div><b>Use su propio Excel o nuestra plantilla</b><small>Si su archivo ya tiene nombre y precio, sirve así. Si no, descargue la plantilla y llénela.</small>
                    <div class="mt-2"><a href="{{ route('productos.importar.plantilla') }}" class="im-btn l" style="padding:.5rem 1rem;font-size:.88rem"><i class="fa-solid fa-download"></i> Descargar plantilla</a></div></div></li>
                <li><span class="n">2</span><div><b>Súbalo aquí</b><small>Excel (.xlsx) o CSV, hasta 10 MB (más de 10.000 productos).</small></div></li>
                <li><span class="n">3</span><div><b>Revise y confirme</b><small>Le mostramos cómo quedó cada columna antes de guardar.</small></div></li>
            </ul>
            <form method="post" action="{{ route('productos.importar.subir') }}" enctype="multipart/form-data" id="fSubir">
                @csrf
                <label class="im-drop" id="imDrop">
                    <input type="file" name="archivo" accept=".xlsx,.xlsm,.xls,.csv,.txt" required>
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <b>Toque para elegir el archivo</b>
                    <small>o arrástrelo aquí</small>
                    <span class="im-nom"></span>
                </label>
                <button class="im-btn g w-100 mt-3" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i> Leer mi archivo</button>
            </form>
            <div>
                <small class="text-muted fw-bold d-block mb-1">Columnas que reconocemos:</small>
                <div class="im-cols">
                    @foreach (\App\Support\ImportarProductos::CAMPOS as $k => $c)
                    @continue(!$tienda->usa_tallas && in_array($k, ['talla', 'color']))
                    <span class="{{ in_array($k, ['nombre', 'precio_venta']) ? 'req' : '' }}">{{ $c }}{{ in_array($k, ['nombre', 'precio_venta']) ? ' *' : '' }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Opción 2: lo hace el equipo --}}
        <div class="im-card">
            <h2><span class="ic"><i class="fa-solid fa-headset"></i></span> Que LocalPro lo haga por mí</h2>
            <p>¿Tiene los productos en otro sistema, en un PDF, en fotos o en un Excel desordenado? Envíenoslo: nuestro equipo los carga y le avisa cuando estén listos.</p>
            <form method="post" action="{{ route('productos.importar.solicitar') }}" enctype="multipart/form-data" class="d-flex flex-column gap-3">
                @csrf
                <label class="im-drop" id="imDrop2" style="border-color:#dfe6e2;background:#fafcfb">
                    <input type="file" name="archivo" accept=".xlsx,.xls,.xlsm,.csv,.txt,.pdf,.jpg,.jpeg,.png,.webp,.zip,.ods,.doc,.docx" required>
                    <i class="fa-solid fa-paperclip" style="color:#33403a"></i>
                    <b>Adjuntar archivo</b>
                    <small>Excel, PDF, foto, Word o ZIP · hasta 20 MB</small>
                    <span class="im-nom"></span>
                </label>
                <div><label class="form-label">WhatsApp para avisarle</label><input name="telefono" class="form-control" maxlength="20" inputmode="tel" required value="{{ old('telefono', auth()->user()->telefono ?? $tienda->telefono) }}"></div>
                <div><label class="form-label">¿Algo que debamos saber? <span class="text-muted fw-normal">(opcional)</span></label><textarea name="nota" class="form-control" rows="2" maxlength="1000" placeholder="Ej: los precios de la columna C son al por mayor">{{ old('nota') }}</textarea></div>
                <div class="im-acc">
                    <button class="im-btn n" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar al equipo</button>
                    @if ($soporte)
                    <a class="im-btn w" target="_blank" href="https://wa.me/{{ $soporte }}?text={{ rawurlencode('Hola, soy de ' . $tienda->nombre . ' y quiero que me ayuden a cargar mis productos en LocalPro.') }}"><i class="fa-brands fa-whatsapp"></i> Escribir por WhatsApp</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if ($historial->isNotEmpty())
    <div class="lp-box">
        <h3><span><i class="fa-solid fa-clock-rotate-left me-1"></i> Importaciones anteriores</span></h3>
        <ul class="lp-list im-hist">
            @foreach ($historial as $h)
            <li>
                <div class="av"><i class="fa-solid {{ $h->tipo === 'solicitud' ? 'fa-headset' : 'fa-file-excel' }}"></i></div>
                <div class="t"><b>{{ $h->nombre_original ?: 'Archivo' }}</b>
                    <small>{{ $h->created_at->locale('es')->diffForHumans() }} · {{ $h->tipo === 'solicitud' ? 'Enviado al equipo' : 'Importado por usted' }}{{ $h->estado === 'lista' && ($h->creados || $h->actualizados) ? ' · ' . $h->creados . ' creados, ' . $h->actualizados . ' actualizados' : '' }}</small></div>
                <span class="est {{ $h->estado }}">{{ ['pendiente' => $h->tipo === 'solicitud' ? 'En proceso' : 'Sin terminar', 'lista' => 'Listo', 'rechazada' => 'Revisar'][$h->estado] ?? $h->estado }}</span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection

@push('js')
<script>
['imDrop', 'imDrop2'].forEach(function (id) {
    var d = document.getElementById(id); if (!d) return;
    var inp = d.querySelector('input'), nom = d.querySelector('.im-nom');
    inp.addEventListener('change', function () { var f = inp.files[0]; d.classList.toggle('lleno', !!f); nom.textContent = f ? '📄 ' + f.name : ''; });
    ['dragenter', 'dragover'].forEach(function (e) { d.addEventListener(e, function () { d.classList.add('sobre'); }); });
    ['dragleave', 'drop'].forEach(function (e) { d.addEventListener(e, function () { d.classList.remove('sobre'); }); });
});
document.getElementById('fSubir').addEventListener('submit', function () { var b = this.querySelector('button'); b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Leyendo…'; });
</script>
@endpush
