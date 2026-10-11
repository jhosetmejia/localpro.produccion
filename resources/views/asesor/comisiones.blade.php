@extends('layouts.app')

@section('title', 'Mis comisiones')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-coins me-1"></i>Red comercial</small><h1>Mis <span>comisiones</span></h1></div>
        <div class="lp-quick">
            @foreach (['' => 'Todas', 'pendiente' => 'Por cobrar', 'pagada' => 'Pagadas'] as $k => $v)
            <a href="{{ route('asesor.comisiones', $k ? ['estado' => $k] : []) }}" class="btn {{ ($estado ?? '') === $k ? 'btn-dark' : 'btn-light' }}">{{ $v }}</a>
            @endforeach
        </div>
    </div>
    <div class="lp-box">
        <ul class="lp-list">
            @forelse ($lista as $c)
            <li><div class="av"><i class="fa-solid fa-coins"></i></div>
                <div class="t"><b>{{ $f($c->monto) }} · {{ $c->tienda->nombre ?? 'Negocio' }}</b><small>{{ $c->concepto_nombre }} · {{ $c->tipo === 'nueva' ? 'venta nueva' : 'renovación' }} · {{ $c->created_at->format('d/m/Y') }}{{ $c->pagada_at ? ' · pagada el ' . $c->pagada_at->format('d/m/Y') : '' }}</small></div>
                <span class="badge {{ $c->estado === 'pagada' ? 'bg-success' : ($c->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ucfirst($c->estado) }}</span></li>
            @empty
            <li class="lp-empty">No hay comisiones aquí.</li>
            @endforelse
        </ul>
        <div class="mt-3">{{ $lista->links() }}</div>
    </div>
</div>
@endsection
