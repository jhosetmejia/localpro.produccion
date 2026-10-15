@extends('layouts.app')

@section('title', 'Plantillas de obituarios')

@push('css')
<style>
.op-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem}
.op-card{background:#fff;border:1.5px solid #e3efe5;border-radius:18px;overflow:hidden;display:flex;flex-direction:column}
.op-card [data-obi-marco]{background:#111}
.op-card .cuerpo{padding:.8rem;display:flex;flex-direction:column;gap:.4rem;flex:1}
.op-card small{color:#6b7570}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Obituarios</small><h1>Plantillas <span>de pantalla</span></h1>
            <p class="text-muted mb-0">Arme la plantilla una vez (diseño, medida, fondo o video y animación) y úsela para todos los obituarios.</p></div>
        <div class="lp-quick">
            <a href="{{ route('obituarios.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Obituarios</a>
            <a href="{{ route('obituarios.plantillas.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nueva plantilla</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="op-grid">
        @foreach ($plantillas as $pl)
        @php $mini = tap(clone $pl, fn ($x) => $x->animacion = 'ninguna'); @endphp
        <div class="op-card">
            <a href="{{ route('obituarios.plantillas.edit', $pl) }}" data-obi-marco>@include('obituarios._lienzo', ['p' => $mini, 'o' => null, 'tienda' => $tienda])</a>
            <div class="cuerpo">
                <b>{{ $pl->nombre }}</b>
                <small>{{ \App\Models\ObituarioPlantilla::DISENOS[$pl->diseno][0] ?? $pl->diseno }} · {{ \App\Models\ObituarioPlantilla::MEDIDAS[$pl->medida][0] ?? $pl->medida }}</small>
                <small>Fondo: {{ \App\Models\ObituarioPlantilla::FONDOS[$pl->fondo_tipo] ?? '' }} · {{ \App\Models\ObituarioPlantilla::ANIMACIONES[$pl->animacion] ?? '' }} · Usada {{ $pl->obituarios_count }} {{ $pl->obituarios_count === 1 ? 'vez' : 'veces' }}</small>
                <div class="d-flex gap-1 flex-wrap mt-auto pt-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('obituarios.create', ['plantilla' => $pl->id]) }}"><i class="fa-solid fa-plus me-1"></i>Usar</a>
                    <a class="btn btn-sm btn-dark" href="{{ route('obituarios.plantillas.edit', $pl) }}"><i class="fa-solid fa-pen me-1"></i>Editar</a>
                    <form method="post" action="{{ route('obituarios.plantillas.duplicar', $pl) }}">@csrf<button class="btn btn-sm btn-light" title="Copiar"><i class="fa-regular fa-copy"></i></button></form>
                    @if ($plantillas->count() > 1)
                    <form method="post" action="{{ route('obituarios.plantillas.destroy', $pl) }}" data-confirm="¿Eliminar la plantilla «{{ $pl->nombre }}»?" data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('delete')<button class="btn btn-sm btn-light" title="Eliminar"><i class="fa-solid fa-trash"></i></button></form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/obituarios.js') }}?v=5"></script>
@endpush
