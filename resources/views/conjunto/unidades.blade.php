@extends('layouts.app')

@section('title', 'Unidades')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1>Unidades y <span>residentes</span></h1>
            <span class="text-muted fw-semibold">{{ $unidades->count() }} unidades · cartera {{ dinero($saldos->sum()) }}</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <details class="lp-box mb-3" @if ($unidades->isEmpty()) open @endif>
        <summary class="fw-bold" style="cursor:pointer"><i class="fa-solid fa-layer-group me-1" style="color:#16b34a"></i> Crear muchas unidades de una vez</summary>
        <form method="post" action="{{ route('conjunto.unidades.lote') }}" class="row g-2 mt-1">
            @csrf
            <div class="col-md-4"><label class="form-label">Torres (separadas por coma)</label><input name="torres" class="form-control" placeholder="Torre 1, Torre 2 (vacío si no hay)"></div>
            <div class="col-4 col-md-2"><label class="form-label">Piso desde</label><input type="number" name="piso_desde" class="form-control" value="1" min="0"></div>
            <div class="col-4 col-md-2"><label class="form-label">Piso hasta</label><input type="number" name="piso_hasta" class="form-control" value="5" min="0"></div>
            <div class="col-4 col-md-2"><label class="form-label">Aptos por piso</label><input type="number" name="por_piso" class="form-control" value="4" min="1"></div>
            <div class="col-6 col-md-2"><label class="form-label">Tipo</label><select name="tipo" class="form-select">@foreach (\App\Models\Conjunto\Unidad::TIPOS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div class="col-6 col-md-3"><label class="form-label">Cuota mensual</label><input data-dinero type="number" name="cuota" class="form-control" min="0" value="0"></div>
            <div class="col-12 small text-muted">Ejemplo: pisos 1 a 5 con 4 por piso crea 101, 102, 103, 104… hasta 504. Para casas use piso 0 (crea 1, 2, 3…).</div>
            <div class="col-12"><button class="btn btn-dark">Crear unidades</button></div>
        </form>
    </details>

    <details class="lp-box mb-3">
        <summary class="fw-bold" style="cursor:pointer"><i class="fa-solid fa-plus me-1" style="color:#16b34a"></i> Agregar una unidad</summary>
        <form method="post" action="{{ route('conjunto.unidades.store') }}" class="row g-2 mt-1">
            @csrf
            @include('conjunto._form_unidad', ['u' => null])
            <div class="col-12"><button class="btn btn-primary">Guardar unidad</button></div>
        </form>
    </details>

    <div class="lp-box">
        <form method="get" class="mb-3"><input name="q" class="form-control" placeholder="Buscar por número, torre, propietario o residente" value="{{ $q }}"></form>
        <ul class="lp-list">
            @forelse ($unidades as $u)
            @php $s = (float) ($saldos[$u->id] ?? 0); @endphp
            <li>
                <div class="av" style="{{ $s > 0 ? 'background:#ffe9f3;color:#b0206a' : '' }}"><i class="fa-solid {{ $u->tipo === 'casa' ? 'fa-house' : ($u->tipo === 'local' ? 'fa-store' : 'fa-door-closed') }}"></i></div>
                <div class="t"><b><a href="{{ route('conjunto.unidad', $u) }}" class="text-decoration-none" style="color:inherit">{{ $u->nombre }}</a></b><small>{{ $u->persona ?: 'Sin propietario' }} · cuota {{ dinero($u->cuota) }}{{ $u->activa ? '' : ' · inactiva' }}</small></div>
                <span class="fw-bold {{ $s > 0 ? 'text-danger' : 'text-success' }}" style="white-space:nowrap">{{ $s > 0 ? dinero($s) : 'Al día' }}</span>
            </li>
            @empty
            <li class="lp-empty">Aún no hay unidades. Créelas arriba.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
