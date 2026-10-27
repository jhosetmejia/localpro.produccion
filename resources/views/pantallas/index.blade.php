@extends('layouts.app')

@section('title', 'Pantallas')

@push('css')
<style>
.pt-card{border:1.5px solid #e3efe5;border-radius:18px;padding:.9rem 1rem;background:#fff;margin-bottom:.8rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
.pt-card .ic{width:56px;height:56px;border-radius:14px;background:#eaf7ee;color:#11702f;display:grid;place-items:center;font-size:1.5rem;flex:0 0 56px}
.pt-card .tx{flex:1;min-width:200px}
.pt-card .tx b{font-size:1.05rem}
.pt-codigo{font-size:.9rem;margin-top:.15rem}.pt-codigo b{font-size:1.1rem;color:#0b5a25;background:#eaf7ee;border-radius:8px;padding:.1rem .45rem;white-space:nowrap}
.pt-pasos{background:#f6faf7;border:1.5px dashed #cfe0d4;border-radius:14px;padding:.7rem .9rem;font-size:.88rem}
.pt-pasos ol{padding-left:1.1rem}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Su negocio</small><h1>Pantallas <span>sin cables ni USB</span></h1>
            <p class="text-muted mb-0">Cada pantalla es un televisor. Súbale imágenes o videos y salen uno tras otro, solos.</p></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="lp-box">
                <h3><i class="fa-solid fa-tv me-1"></i>Sus pantallas</h3>
                @forelse ($pantallas as $p)
                <div class="pt-card">
                    <div class="ic"><i class="fa-solid fa-tv"></i></div>
                    <div class="tx">
                        <b>{{ $p->nombre }}</b>
                        @if (!$p->activa)<span class="badge bg-secondary ms-1">Apagada</span>@endif
                        <div class="small text-muted">{{ $p->activos_count }} {{ $p->activos_count === 1 ? 'archivo' : 'archivos' }} en pantalla</div>
                        @if ($p->codigo)<div class="pt-codigo">En el TV escriba: <b>{{ $p->url_corta }}</b></div>@endif
                    </div>
                    <div class="d-flex gap-1">
                        <a class="btn btn-sm btn-light" target="_blank" href="{{ $p->url_pantalla }}" title="Ver la pantalla"><i class="fa-solid fa-up-right-from-square"></i></a>
                        <a class="btn btn-sm btn-primary" href="{{ route('pantallas.show', $p) }}"><i class="fa-solid fa-plus me-1"></i>Imágenes y videos</a>
                    </div>
                </div>
                @empty
                <p class="text-muted">Aún no tiene pantallas. Cree la primera.</p>
                @endforelse
                <form method="post" action="{{ route('pantallas.store') }}" class="input-group mt-2">
                    @csrf
                    <input class="form-control" name="nombre" maxlength="60" required placeholder="Nombre (ej: TV de la entrada, Pantalla {{ $pantallas->count() + 1 }})">
                    <button class="btn btn-dark"><i class="fa-solid fa-plus me-1"></i>Crear pantalla</button>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="lp-box">
                <h3><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Así de fácil</h3>
                <ol class="small">
                    <li>Cree una pantalla por cada televisor (ej: "TV de la entrada", "TV de la caja").</li>
                    <li>Toque <b>Imágenes y videos</b> y agregue lo que quiere mostrar: promociones, menú, precios, videos de su marca.</li>
                    <li>En el televisor abra el código una sola vez y déjelo abierto.</li>
                </ol>
                @include('pantallas._tv-pasos')
            </div>
        </div>
    </div>
</div>
@endsection
