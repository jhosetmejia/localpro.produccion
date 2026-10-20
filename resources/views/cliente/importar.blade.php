@extends('layouts.app')

@section('title', 'Subir base de datos de ' . $cfg['plural'])

@push('css')
<style>
.ic-wrap{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:1rem}
@media (max-width:900px){.ic-wrap{grid-template-columns:minmax(0,1fr)}}
.ic-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:24px;padding:1.4rem;min-width:0}
.ic-card.v{border-color:#bfeccb;background:linear-gradient(180deg,#f4fcf6,#fff 45%)}
.ic-card h2{font-size:1.2rem;font-weight:900;margin:0 0 .4rem;display:flex;align-items:center;gap:.6rem}
.ic-card h2 .ic{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:linear-gradient(135deg,#a6f34a,#16b34a);color:#0b0d0c;flex:none}
.ic-card p{color:#5b665e;font-weight:500}
.ic-drop{position:relative;border:2px dashed #bfeccb;border-radius:20px;background:#f7fdf8;padding:2rem 1rem;text-align:center;cursor:pointer;transition:all .15s;margin:1rem 0}
.ic-drop:hover,.ic-drop.sobre{border-color:#16b34a;background:#ecfaef}
.ic-drop input{position:absolute;inset:0;opacity:0;cursor:pointer}
.ic-drop i{font-size:2.4rem;color:#16b34a}
.ic-drop b{display:block;margin-top:.5rem;font-size:1.05rem}
.ic-drop small{color:#6b7570}
.ic-drop .nom{display:none;margin-top:.6rem;font-weight:800;color:#11702f;word-break:break-all}
.ic-drop.lleno .nom{display:block}
.ic-btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border:0;border-radius:999px;padding:.85rem 1.4rem;font-weight:800;text-decoration:none;cursor:pointer;width:100%}
.ic-btn.g{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c}
.ic-btn.l{background:#f1f5f2;color:#0b0d0c;margin-top:.6rem}
.ic-cols{display:flex;flex-wrap:wrap;gap:.4rem;margin:.6rem 0 1rem}
.ic-cols span{background:#dcfce7;color:#14753a;border-radius:10px;padding:.3rem .7rem;font-size:.85rem;font-weight:800}
.ic-o{display:flex;align-items:center;gap:.6rem;color:#6b7570;font-weight:700;font-size:.85rem;margin:.2rem 0 .7rem}
.ic-o:before,.ic-o:after{content:"";flex:1;height:1px;background:#e3ebe6}
.ic-pasos{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.9rem}
.ic-pasos li{display:flex;gap:.8rem}
.ic-pasos .n{width:30px;height:30px;border-radius:50%;background:#0b0d0c;color:#a6f34a;font-weight:900;display:grid;place-items:center;flex:none}
.ic-pasos b{display:block}.ic-pasos small{color:#6b7570;font-weight:500}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid {{ $cfg['icono'] }} me-1"></i>{{ $cfg['Plural'] }}</small><h1>Subir base de datos de <span>{{ $cfg['plural'] }}</span></h1>
            <span class="text-muted fw-semibold">Suba su Excel y no tenga que volver a crearlos uno por uno.</span></div>
        <div class="lp-quick"><a href="{{ route($cfg['ruta'] . '.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>{{ $cfg['volver'] }}</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="ic-wrap">
        <form class="ic-card v" method="post" action="{{ route($cfg['ruta'] . '.importar.subir') }}" enctype="multipart/form-data">
            @csrf
            <h2><span class="ic"><i class="fa-solid fa-wand-magic-sparkles"></i></span> Convierta cualquier Excel en {{ $cfg['plural'] }}</h2>
            <p class="mb-0">Sirve {{ $cfg['ejemplo'] }} <b>cualquier archivo con datos de {{ $cfg['quien'] }}</b>: Excel, CSV, contactos del celular o una hoja de Google. LocalPro busca solo las columnas importantes:</p>
            <div class="ic-cols"><span>{{ $cfg['tipo'] === 'proveedores' ? 'Nombre o empresa' : 'Nombre' }}</span><span>Cédula / NIT</span><span>Celular</span><span>Correo</span><span>Dirección</span></div>
            <label class="ic-drop" id="drop">
                <input type="file" name="archivo" accept=".xlsx,.xlsm,.csv,.txt,.xls,.vcf" onchange="var d=document.getElementById('drop');d.classList.toggle('lleno',!!this.files.length);d.querySelector('.nom').textContent=this.files.length?this.files[0].name:''">
                <i class="fa-solid fa-file-excel"></i>
                <b>Toque aquí o arrastre su archivo</b>
                <small>Excel (.xlsx), CSV o contactos (.vcf)</small>
                <div class="nom"></div>
            </label>
            <div class="ic-o"><span>o pegue el enlace de su hoja de Google</span></div>
            <input type="url" name="enlace" class="form-control form-control-lg mb-3" placeholder="https://docs.google.com/spreadsheets/d/…" value="{{ old('enlace') }}">
            <small class="text-muted d-block mb-3" style="margin-top:-.6rem">La hoja debe estar compartida como "Cualquier persona con el enlace".</small>
            <button class="ic-btn g"><i class="fa-solid fa-magnifying-glass"></i> Buscar los datos en mi archivo</button>
        </form>

        <div class="ic-card">
            <h2 style="font-size:1.05rem">¿Cómo funciona?</h2>
            <ol class="ic-pasos mt-3">
                <li><span class="n">1</span><div><b>Suba su archivo</b><small>No importa el orden de las columnas ni cómo se llamen.</small></div></li>
                <li><span class="n">2</span><div><b>Revise lo que encontramos</b><small>Le mostramos qué columna es el nombre, la cédula o NIT, el celular… y puede cambiarla si quiere.</small></div></li>
                <li><span class="n">3</span><div><b>Toque "Crear {{ $cfg['plural'] }}"</b><small>{{ $cfg['listo'] }} Los que ya tenía no se repiten.</small></div></li>
            </ol>
             <a href="{{ route($cfg['ruta'] . '.importar.plantilla') }}" class="ic-btn l mt-3"><i class="fa-solid fa-download"></i> ¿No tiene Excel? Descargue la plantilla</a>
        </div>
    </div>
</div>
<script>
(function () {
    var d = document.getElementById('drop');
    ['dragenter', 'dragover'].forEach(function (e) { d.addEventListener(e, function () { d.classList.add('sobre'); }); });
    ['dragleave', 'drop'].forEach(function (e) { d.addEventListener(e, function () { d.classList.remove('sobre'); }); });
})();
</script>
@endsection
