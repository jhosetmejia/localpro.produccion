@extends('layouts.app')

@section('title', 'Gastos del conjunto')

@section('content')
@php $total = $gastos->sum('valor'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1>Gastos e <span>ingresos</span></h1>
            <span class="text-muted fw-semibold">Recaudo {{ dinero($recaudo) }} − gastos {{ dinero($total) }} = <b class="{{ $recaudo - $total < 0 ? 'text-danger' : 'text-success' }}">{{ dinero($recaudo - $total) }}</b></span></div>
        <form method="get" class="lp-quick"><input type="month" name="mes" class="form-control" value="{{ $mes }}" onchange="this.form.submit()"></form>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3>Registrar gasto</h3>
            <form method="post" action="{{ route('conjunto.gastos.store') }}" class="row g-2">
                @csrf
                <div class="col-6"><input type="date" name="fecha" class="form-control" value="{{ today()->format('Y-m-d') }}" required></div>
                <div class="col-6"><select name="categoria" class="form-select">@foreach (\App\Models\Conjunto\Gasto::CATEGORIAS as $c)<option>{{ $c }}</option>@endforeach</select></div>
                <div class="col-12"><input name="concepto" class="form-control" maxlength="160" required placeholder="Concepto (ej. Pago vigilancia octubre)"></div>
                <div class="col-6"><input data-dinero type="number" name="valor" class="form-control" min="1" required placeholder="Valor"></div>
                <div class="col-6"><input name="proveedor" class="form-control" maxlength="80" placeholder="Proveedor"></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Guardar gasto</button></div>
            </form>
        </div>
        <div class="lp-box">
            <h3>Por categoría</h3>
            @forelse ($porCategoria as $cat => $v)
            <div class="mb-2"><div class="d-flex justify-content-between small fw-bold"><span>{{ $cat }}</span><span>{{ dinero($v) }}</span></div>
                <div style="height:8px;border-radius:99px;background:#eef3ef;overflow:hidden"><div style="height:100%;width:{{ $total ? $v / $total * 100 : 0 }}%;background:linear-gradient(90deg,#a6f34a,#16b34a)"></div></div></div>
            @empty
            <p class="lp-empty mb-0">Sin gastos este mes.</p>
            @endforelse
        </div>
    </div>
    <div class="lp-box">
        <ul class="lp-list">
            @forelse ($gastos as $g)
            <li><div class="av"><i class="fa-solid fa-wallet"></i></div><div class="t"><b>{{ dinero($g->valor) }} · {{ $g->concepto }}</b><small>{{ $g->fecha->format('d/m/Y') }} · {{ $g->categoria }}{{ $g->proveedor ? ' · ' . $g->proveedor : '' }}</small></div>
                <form method="post" action="{{ route('conjunto.gastos.destroy', $g) }}" data-confirm="¿Eliminar este gasto?" data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('DELETE')<button class="btn btn-sm btn-light"><i class="fa-solid fa-trash"></i></button></form></li>
            @empty
            <li class="lp-empty">Sin gastos registrados en este mes.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
