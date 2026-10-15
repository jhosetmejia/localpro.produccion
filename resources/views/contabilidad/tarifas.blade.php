@extends('layouts.app')

@section('title', 'Tabla de retenciones')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Contabilidad</small><h1>Tabla de <span>retenciones</span></h1>
            <p class="text-muted mb-0">Bases en UVT y tarifas por concepto. Puede tener varias filas del mismo concepto con fechas distintas: para cada factura se usa la que estaba vigente en su fecha.</p></div>
        <div class="lp-quick"><a href="{{ route('contabilidad.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Clientes</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="alert alert-warning small">
        <b>Ojo con la norma:</b> el Decreto 572 de 2025 cambió bases y tarifas, y el Consejo de Estado suspendió sus artículos 2 al 8 desde el 8 de mayo de 2026 (auto del 7 de mayo de 2026); desde esa fecha volvieron las bases y tarifas anteriores, que son las que trae esta tabla.
        Si revisa meses en que rigió el decreto, agregue esas filas con sus fechas "desde / hasta". Verifique siempre contra la norma vigente.
        UVT: @foreach ($uvt as $a => $v){{ $a }}: ${{ number_format($v, 0, ',', '.') }}{{ $loop->last ? '' : ' · ' }}@endforeach
    </div>
    <form method="post" action="{{ route('contabilidad.tarifas.guardar') }}" class="lp-box">
        @csrf
        <div class="table-responsive">
        <table class="table table-sm align-middle" id="tb">
            <thead><tr><th>Concepto</th><th>Nombre</th><th>Base (UVT)</th><th>% declarante / jurídica</th><th>% no declarante / natural</th><th>Desde</th><th>Hasta</th><th>Quitar</th></tr></thead>
            <tbody>
            @foreach ($tarifas as $i => $t)
            <tr>
                <td><input type="hidden" name="t[{{ $i }}][id]" value="{{ $t->id }}">
                    <select class="form-select form-select-sm" name="t[{{ $i }}][concepto]">@foreach (\App\Support\Retenciones::CONCEPTOS as $k => [$n])@continue($k === 'no_aplica')<option value="{{ $k }}" @selected($t->concepto === $k)>{{ $n }}</option>@endforeach</select></td>
                <td><input class="form-control form-control-sm" name="t[{{ $i }}][nombre]" value="{{ $t->nombre }}" maxlength="100"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" style="width:90px" name="t[{{ $i }}][base_uvt]" value="{{ $t->base_uvt + 0 }}"></td>
                <td><input type="number" step="0.001" class="form-control form-control-sm" style="width:90px" name="t[{{ $i }}][tarifa_declarante]" value="{{ $t->tarifa_declarante + 0 }}"></td>
                <td><input type="number" step="0.001" class="form-control form-control-sm" style="width:90px" name="t[{{ $i }}][tarifa_no_declarante]" value="{{ $t->tarifa_no_declarante + 0 }}"></td>
                <td><input type="date" class="form-control form-control-sm" name="t[{{ $i }}][desde]" value="{{ $t->desde?->format('Y-m-d') }}"></td>
                <td><input type="date" class="form-control form-control-sm" name="t[{{ $i }}][hasta]" value="{{ $t->hasta?->format('Y-m-d') }}"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input" name="t[{{ $i }}][borrar]" value="1"></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <button type="button" class="btn btn-light btn-sm" id="agregar"><i class="fa-solid fa-plus me-1"></i>Agregar fila</button>
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar tabla</button>
    </form>
    <form method="post" action="{{ route('contabilidad.tarifas.restaurar') }}" data-confirm="¿Volver a la tabla general? Se pierden los cambios." data-confirm-ok="Sí, restaurar" class="mt-2">@csrf<button class="btn btn-link">Restaurar tabla general</button></form>
</div>
@endsection

@push('js')
<script>
document.getElementById('agregar').addEventListener('click', function () {
    var tb = document.querySelector('#tb tbody'), ult = tb.lastElementChild, n = tb.children.length;
    var nueva = ult.cloneNode(true);
    nueva.querySelectorAll('[name]').forEach(function (i) {
        i.name = i.name.replace(/t\[\d+\]/, 't[' + n + ']');
        if (i.name.endsWith('[id]') || i.type === 'date') i.value = '';
        if (i.type === 'checkbox') i.checked = false;
    });
    tb.appendChild(nueva);
});
</script>
@endpush
