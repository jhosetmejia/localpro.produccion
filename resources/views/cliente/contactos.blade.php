@extends('layouts.app')

@section('title', 'Crear base de datos de clientes')

@push('css')
<style>
.cb-wrap{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:1rem;align-items:start}
@media (max-width:900px){.cb-wrap{grid-template-columns:minmax(0,1fr)}}
.cb-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:24px;padding:1.3rem;min-width:0}
.cb-card.v{border-color:#bfeccb;background:linear-gradient(180deg,#f4fcf6,#fff 45%)}
.cb-card h2{font-size:1.15rem;font-weight:900;margin:0 0 .5rem;display:flex;align-items:center;gap:.6rem}
.cb-card h2 .n{width:32px;height:32px;border-radius:10px;background:#0b0d0c;color:#a6f34a;display:grid;place-items:center;font-size:.95rem;flex:none}
.cb-card p{color:#5b665e;font-weight:500}
.cb-tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin:.6rem 0 1rem}
.cb-tabs button{border:1.5px solid #e3ebe6;background:#fff;border-radius:999px;padding:.45rem .9rem;font-weight:800;font-size:.88rem;display:flex;align-items:center;gap:.4rem}
.cb-tabs button.on{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.cb-paso{display:none}.cb-paso.on{display:block}
.cb-paso ol{padding-left:0;list-style:none;margin:0;display:flex;flex-direction:column;gap:.7rem;counter-reset:p}
.cb-paso li{display:flex;gap:.7rem;counter-increment:p;font-weight:600;line-height:1.4}
.cb-paso li::before{content:counter(p);width:26px;height:26px;border-radius:50%;background:#dcfce7;color:#11702f;font-weight:900;display:grid;place-items:center;flex:none;font-size:.85rem}
.cb-paso li small{display:block;color:#6b7570;font-weight:500}
.cb-paso kbd{background:#f1f5f2;color:#0b0d0c;border-radius:6px;padding:.05rem .4rem;font-weight:800;font-size:.85em;border:1px solid #e3ebe6;box-shadow:none}
.cb-drop{position:relative;border:2px dashed #bfeccb;border-radius:20px;background:#f7fdf8;padding:1.8rem 1rem;text-align:center;cursor:pointer;margin:1rem 0}
.cb-drop:hover{border-color:#16b34a;background:#ecfaef}
.cb-drop input{position:absolute;inset:0;opacity:0;cursor:pointer}
.cb-drop i{font-size:2.2rem;color:#16b34a}
.cb-drop b{display:block;margin-top:.5rem}
.cb-drop small{color:#6b7570}
.cb-drop .nom{display:none;margin-top:.5rem;font-weight:800;color:#11702f;word-break:break-all}
.cb-drop.lleno .nom{display:block}
.cb-btn{display:flex;align-items:center;justify-content:center;gap:.5rem;border:0;border-radius:999px;padding:.85rem 1.4rem;font-weight:800;width:100%;background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c}
.cb-nota{background:#fffbeb;border:1.5px solid #fde68a;border-radius:16px;padding:.8rem 1rem;font-size:.88rem;color:#78501a;margin-top:1rem}
.cb-ideas li{margin-bottom:.4rem}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-address-book me-1"></i>Clientes</small><h1>Crear base de datos de <span>clientes</span></h1>
            <span class="text-muted fw-semibold">Empiece con los contactos de su celular: en minutos tiene una base de datos para llegar a clientes nuevos.</span></div>
        <div class="lp-quick"><a href="{{ route('clientes.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Mis clientes</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="cb-wrap">
        <div class="cb-card">
            <h2><span class="n">1</span> Saque los contactos de su celular</h2>
            <p class="mb-0">Elija su celular y siga los pasos. Al final le queda un archivo de contactos (termina en <b>.vcf</b> o <b>.csv</b>).</p>
            <div class="cb-tabs" id="cbTabs">
                <button type="button" class="on" data-t="android"><i class="fa-brands fa-android"></i> Android</button>
                <button type="button" data-t="samsung"><i class="fa-solid fa-mobile-screen"></i> Samsung</button>
                <button type="button" data-t="iphone"><i class="fa-brands fa-apple"></i> iPhone</button>
                <button type="button" data-t="google"><i class="fa-brands fa-google"></i> Google Contactos (computador)</button>
            </div>

            <div class="cb-paso on" data-p="android">
                <ol>
                    <li><span>Abra la app <kbd>Contactos</kbd> de Google en su celular.</span></li>
                    <li><span>Toque <kbd>Arreglar y administrar</kbd> (abajo) o el menú <kbd>☰</kbd> / su foto arriba a la derecha.<small>En algunos celulares dice "Configuración" o "Administrar contactos".</small></span></li>
                    <li><span>Toque <kbd>Exportar a un archivo</kbd> (o "Exportar").</span></li>
                    <li><span>Elija la cuenta de donde quiere sacar los contactos y toque <kbd>Exportar</kbd>.<small>Se guarda un archivo "contacts.vcf" en Descargas.</small></span></li>
                    <li><span>Suba ese archivo aquí al lado (paso 2), desde el mismo celular.</span></li>
                </ol>
            </div>
            <div class="cb-paso" data-p="samsung">
                <ol>
                    <li><span>Abra la app <kbd>Contactos</kbd> de Samsung.</span></li>
                    <li><span>Toque el menú <kbd>☰</kbd> (tres rayitas) y luego <kbd>Administrar contactos</kbd>.</span></li>
                    <li><span>Toque <kbd>Importar o exportar contactos</kbd> → <kbd>Exportar</kbd>.</span></li>
                    <li><span>Elija <kbd>Almacenamiento interno</kbd> y toque <kbd>Exportar</kbd>.<small>Queda un archivo .vcf en la carpeta "Mis archivos" del celular.</small></span></li>
                    <li><span>Suba ese archivo aquí al lado (paso 2).</span></li>
                </ol>
            </div>
            <div class="cb-paso" data-p="iphone">
                <ol>
                    <li><span>Abra la app <kbd>Contactos</kbd> y toque <kbd>Listas</kbd> arriba a la izquierda.</span></li>
                    <li><span>Mantenga presionado <kbd>Todos los contactos</kbd> (o "Todos los de iCloud").</span></li>
                    <li><span>Toque <kbd>Exportar</kbd> y luego <kbd>Guardar en Archivos</kbd>.<small>Si su iPhone no muestra "Exportar": entre a icloud.com/contacts en un computador, seleccione todos y use "Exportar vCard".</small></span></li>
                    <li><span>Suba ese archivo aquí al lado (paso 2).</span></li>
                </ol>
            </div>
            <div class="cb-paso" data-p="google">
                <ol>
                    <li><span>En el computador entre a <a href="https://contacts.google.com" target="_blank" rel="noopener"><b>contacts.google.com</b></a> con la cuenta de Gmail de su celular.</span></li>
                    <li><span>Toque <kbd>Exportar</kbd> (en el menú de la izquierda o el ícono de la flecha arriba).</span></li>
                    <li><span>Elija <kbd>Todos los contactos</kbd> y el formato <kbd>CSV de Google</kbd> o <kbd>vCard</kbd> → <kbd>Exportar</kbd>.</span></li>
                    <li><span>Suba ese archivo aquí al lado (paso 2).</span></li>
                </ol>
            </div>
        </div>

        <div>
            <form class="cb-card v" method="post" action="{{ route('clientes.importar.subir') }}" enctype="multipart/form-data">
                @csrf
                <h2><span class="n">2</span> Súbalo aquí</h2>
                <p class="mb-0">LocalPro saca de cada contacto el <b>nombre, el celular y el correo</b>. Antes de guardar le mostramos lo que encontró para que lo revise. No se crean clientes repetidos.</p>
                <label class="cb-drop" id="cbDrop">
                    <input type="file" name="archivo" accept=".vcf,.vcard,.csv,.xlsx,text/vcard,text/x-vcard" required onchange="var d=document.getElementById('cbDrop');d.classList.toggle('lleno',!!this.files.length);d.querySelector('.nom').textContent=this.files.length?this.files[0].name:''">
                    <i class="fa-solid fa-address-card"></i>
                    <b>Toque aquí y elija el archivo de contactos</b>
                    <small>.vcf (contactos del celular) o .csv · hasta 5.000 contactos</small>
                    <div class="nom"></div>
                </label>
                <button class="cb-btn"><i class="fa-solid fa-magnifying-glass"></i> Revisar mis contactos</button>
            </form>

            <div class="cb-card mt-3">
                <h2><span class="n">3</span> Llegue a clientes nuevos</h2>
                <ul class="cb-ideas small mb-0">
                    <li>Comparta su <b>tienda en línea</b> con su base de datos por WhatsApp (estados, listas de difusión).</li>
                    <li>Avise cuando tenga <b>promociones</b> o mercancía nueva.</li>
                    <li>Cuando le compren, el cliente ya está guardado: la venta queda a su nombre.</li>
                </ul>
                <div class="cb-nota"><b>Importante:</b> escríbales a personas que lo conocen y dígales quién es. Si alguien le pide no recibir más mensajes, respételo (Ley 1581 de protección de datos).</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var tabs = document.getElementById('cbTabs');
    function ver(t) {
        tabs.querySelectorAll('button').forEach(function (b) { b.classList.toggle('on', b.dataset.t === t); });
        document.querySelectorAll('.cb-paso').forEach(function (p) { p.classList.toggle('on', p.dataset.p === t); });
    }
    tabs.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) ver(b.dataset.t); });
    // Abre la pestaña de su celular
    var ua = navigator.userAgent;
    if (/iPhone|iPad/i.test(ua)) ver('iphone');
    else if (/SM-|Samsung/i.test(ua)) ver('samsung');
    else if (!/Android/i.test(ua)) ver('google');
})();
</script>
@endpush
