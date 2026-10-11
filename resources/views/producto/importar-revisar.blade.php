@extends('layouts.app')

@section('title', 'Revisar importación')

@push('css')
<style>
.ir-top{display:flex;gap:.8rem;flex-wrap:wrap;margin-bottom:1rem}
.ir-top .k{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.8rem 1.1rem;flex:1 1 160px}
.ir-top .k small{display:block;color:#6b7570;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em}
.ir-top .k b{font-size:1.4rem;font-weight:900}
.ir-map{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.8rem}
.ir-col{border:1.5px solid #e3ebe6;border-radius:18px;padding:.8rem;background:#fff;min-width:0;transition:border-color .15s,background .15s}
.ir-col.ok{border-color:#bfeccb;background:#f7fdf8}
.ir-col .h{font-weight:800;font-size:.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:.15rem}
.ir-col .ej{color:#6b7570;font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:.5rem}
.ir-tabla{overflow-x:auto;border:1.5px solid #e3ebe6;border-radius:18px;background:#fff}
.ir-tabla table{width:100%;border-collapse:collapse;font-size:.85rem;min-width:600px}
.ir-tabla th{background:#f3f6f4;padding:.6rem .7rem;text-align:left;font-weight:800;white-space:nowrap;border-bottom:1.5px solid #e3ebe6}
.ir-tabla th small{display:block;color:#16b34a;font-weight:800;font-size:.7rem;text-transform:uppercase}
.ir-tabla th small.no{color:#b3bab6}
.ir-tabla td{padding:.55rem .7rem;border-bottom:1px solid #f0f3f1;white-space:nowrap;max-width:220px;overflow:hidden;text-overflow:ellipsis}
.ir-tabla td.off{color:#c3c9c5}
.ir-opc{display:flex;flex-direction:column;gap:.6rem}
.ir-opc label{display:flex;gap:.7rem;align-items:flex-start;background:#fff;border:1.5px solid #e3ebe6;border-radius:16px;padding:.8rem 1rem;cursor:pointer;margin:0}
.ir-opc input{margin-top:.25rem;width:18px;height:18px;accent-color:#16b34a;flex:none}
.ir-opc small{display:block;color:#6b7570;font-weight:500}
.ir-falta{background:#ffe9f3;color:#9b1c5a;border-radius:14px;padding:.6rem .9rem;font-weight:700;display:none;margin-bottom:.8rem}
.ir-pie{position:sticky;bottom:0;background:rgba(255,255,255,.94);backdrop-filter:blur(6px);border-top:1px solid #e3ebe6;padding:.8rem 0;margin-top:1rem;display:flex;gap:.6rem;flex-wrap:wrap;justify-content:flex-end;z-index:5}
.ir-pie .btn{border-radius:999px;font-weight:800;padding:.75rem 1.4rem}
.ir-pie .go{background:linear-gradient(90deg,#a3f04a,#16b84e);border:0;color:#0b0d0c}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-file-excel me-1"></i>Importar productos · paso 2 de 2</small><h1>Revise sus <span>columnas</span></h1>
            <span class="text-muted fw-semibold">{{ $imp->nombre_original }}{{ $imp->tipo === 'solicitud' ? ' · enviado por ' . ($imp->tienda->nombre ?? '') : '' }}</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="ir-top">
        <div class="k"><small>Filas en el archivo</small><b>{{ number_format($total, 0, ',', '.') }}</b></div>
        <div class="k"><small>Columnas</small><b>{{ count($cab) }}</b></div>
        <div class="k"><small>Reconocidas solas</small><b>{{ count($mapa) }}</b></div>
    </div>

    <form method="post" action="{{ route('productos.importar.confirmar') }}" id="fImp">
        @csrf
        <input type="hidden" name="con_encabezados" value="{{ $conCab ? 1 : 0 }}">

        <div class="lp-box mb-3">
            <h3><span><i class="fa-solid fa-table-columns me-1"></i> ¿Qué es cada columna?</span><small class="text-muted">Ya lo adivinamos; cambie lo que no esté bien</small></h3>
            <div class="ir-falta" id="irFalta"></div>
            <div class="ir-map">
                @foreach ($cab as $i => $h)
                @php $ej = collect($muestra)->pluck($i)->filter(fn ($v) => $v !== '' && $v !== null)->take(3)->implode(' · '); @endphp
                <div class="ir-col {{ isset($mapa[$i]) ? 'ok' : '' }}">
                    <div class="h" title="{{ $h }}">{{ $h !== '' ? $h : 'Columna ' . ($i + 1) }}</div>
                    <div class="ej" title="{{ $ej }}">{{ $ej ?: 'Sin datos' }}</div>
                    <select name="mapa[{{ $i }}]" class="form-select form-select-sm ir-sel" data-i="{{ $i }}">
                        <option value="">— No importar —</option>
                        @foreach (\App\Support\ImportarProductos::CAMPOS as $k => $c)
                        <option value="{{ $k }}" @selected(($mapa[$i] ?? null) === $k)>{{ $c }}{{ in_array($k, ['nombre', 'precio_venta']) ? ' *' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3><span><i class="fa-solid fa-eye me-1"></i> Así se ven los primeros productos</span></h3>
            <div class="ir-tabla">
                <table>
                    <thead><tr>
                        @foreach ($cab as $i => $h)
                        <th data-th="{{ $i }}">{{ $h !== '' ? $h : 'Columna ' . ($i + 1) }}<small></small></th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @foreach ($muestra as $f)
                        <tr>@foreach ($cab as $i => $h)<td data-td="{{ $i }}">{{ is_float($f[$i] ?? null) ? rtrim(rtrim(number_format($f[$i], 2, '.', ''), '0'), '.') : ($f[$i] ?? '') }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lp-box">
            <h3><span><i class="fa-solid fa-sliders me-1"></i> Opciones</span></h3>
            <div class="ir-opc">
                <label><input type="checkbox" name="actualizar" value="1" checked><span><b>Si el producto ya existe, actualizarlo</b><small>Se busca por código (o por nombre). Se actualiza el precio, el costo y la cantidad queda igual a la del archivo. Así puede subir el archivo varias veces sin duplicar.</small></span></label>
                <label><input type="checkbox" name="controla" value="1" @checked(!in_array($tienda->tipo_negocio, ['restaurante', 'cafeteria']))><span><b>Controlar inventario</b><small>Se descuenta el stock en cada venta y queda el stock actualizado. Apáguelo para comidas preparadas o servicios.</small></span></label>
            </div>
        </div>

        <div class="ir-pie">
            <button type="submit" form="fCancelar" class="btn btn-light">Cancelar</button>
            <button type="submit" class="btn go" id="irGo"><i class="fa-solid fa-file-import me-1"></i> Importar {{ number_format($total, 0, ',', '.') }} filas</button>
        </div>
    </form>
    <form method="post" action="{{ route('productos.importar.cancelar') }}" id="fCancelar">@csrf</form>
</div>
@endsection

@push('js')
<script>
(function () {
    var CAMPOS = @json(\App\Support\ImportarProductos::CAMPOS);
    var sels = [].slice.call(document.querySelectorAll('.ir-sel'));
    var falta = document.getElementById('irFalta'), go = document.getElementById('irGo');
    function revisar(cambio) {
        // Un campo solo en una columna: si se repite, se quita de la otra
        if (cambio && cambio.value) sels.forEach(function (s) { if (s !== cambio && s.value === cambio.value) { s.value = ''; } });
        var usados = {};
        sels.forEach(function (s) {
            var i = s.dataset.i, v = s.value;
            if (v) usados[v] = true;
            s.closest('.ir-col').classList.toggle('ok', !!v);
            var th = document.querySelector('[data-th="' + i + '"] small');
            th.textContent = v ? '→ ' + CAMPOS[v] : 'No se importa'; th.className = v ? '' : 'no';
            document.querySelectorAll('[data-td="' + i + '"]').forEach(function (td) { td.classList.toggle('off', !v); });
        });
        var f = [];
        if (!usados.nombre) f.push('el nombre del producto');
        if (!usados.precio_venta) f.push('el precio de venta');
        falta.style.display = f.length ? 'block' : 'none';
        falta.innerHTML = f.length ? '<i class="fa-solid fa-triangle-exclamation me-1"></i> Falta indicar cuál columna es ' + f.join(' y ') + '.' : '';
        go.disabled = f.length > 0;
    }
    sels.forEach(function (s) { s.addEventListener('change', function () { revisar(s); }); });
    revisar();
    document.getElementById('fImp').addEventListener('submit', function () { go.disabled = true; go.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Importando… no cierre esta pantalla'; });
})();
</script>
@endpush
