@extends('layouts.app')

@section('title', 'Insumos por procedimiento')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-boxes-stacked me-1"></i>Consultorio odontológico</small><h1>Insumos por <span>procedimiento</span></h1>
            <span class="text-muted fw-semibold">Diga qué gasta cada procedimiento: al marcarlo hecho en el plan, se descuenta solo del inventario.</span></div>
    </div>
    @include('consultorio._nav')
    @include('odontologia._sub')

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="cs-card">
                <h3>Agregar insumo</h3>
                @if (!$servicios->count() || !$insumos->count())
                <div class="lp-empty">Primero cree en <b>Productos</b> sus procedimientos como <b>Servicio</b> (resina, exodoncia…) y sus insumos como <b>Producto</b> con existencias (resina A2, carpules de anestesia, guantes…).
                    <div class="mt-2"><a href="{{ route('productos.create') }}" class="cs-btn sm dark"><i class="fa-solid fa-plus"></i>Crear producto</a></div></div>
                @else
                <form method="post" action="{{ route('odonto.insumo.guardar') }}" class="d-grid gap-2">
                    @csrf
                    <label class="small fw-bold" style="color:var(--lp-muted)">Procedimiento (servicio)</label>
                    <select name="producto_id" class="form-select" required>@foreach ($servicios as $s)<option value="{{ $s->id }}">{{ $s->nombre }}</option>@endforeach</select>
                    <label class="small fw-bold" style="color:var(--lp-muted)">Insumo que gasta</label>
                    <select name="producto_variante_id" class="form-select" required>@foreach ($insumos as $v)<option value="{{ $v->id }}">{{ $v->producto?->nombre }}{{ $v->etiqueta !== 'Única' ? ' · ' . $v->etiqueta : '' }} (hay {{ rtrim(rtrim(number_format((float) $v->stock, 3, ',', '.'), '0'), ',') }})</option>@endforeach</select>
                    <label class="small fw-bold" style="color:var(--lp-muted)">Cantidad por procedimiento</label>
                    <input type="number" name="cantidad" class="form-control" min="0.001" step="0.001" value="1" required>
                    <button class="cs-btn prim justify-content-center"><i class="fa-solid fa-plus"></i>Guardar</button>
                    <small class="text-muted">Si el procedimiento lleva cantidad (ej. resina × 2 superficies), se descuenta esa cantidad multiplicada. Si lo desmarca como hecho, los insumos vuelven al inventario.</small>
                </form>
                @endif
            </div>
        </div>
        <div class="col-lg-7">
            <div class="cs-card">
                <h3>Lo que gasta cada procedimiento</h3>
                @forelse ($servicios->filter(fn ($s) => isset($config[$s->id])) as $s)
                <div class="py-2 border-bottom">
                    <b>{{ $s->nombre }}</b>
                    @foreach ($config[$s->id] as $ins)
                    <div class="d-flex justify-content-between align-items-center small py-1">
                        <span>• {{ rtrim(rtrim(number_format($ins->cantidad, 3, ',', '.'), '0'), ',') }} × {{ $ins->variante?->producto?->nombre }}{{ $ins->variante && $ins->variante->etiqueta !== 'Única' ? ' · ' . $ins->variante->etiqueta : '' }}</span>
                        <form method="post" action="{{ route('odonto.insumo.borrar', $ins) }}">@csrf @method('DELETE')<button class="btn btn-link text-danger p-0" title="Quitar"><i class="fa-solid fa-trash-can"></i></button></form>
                    </div>
                    @endforeach
                </div>
                @empty
                <div class="lp-empty">Todavía no ha configurado insumos.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
