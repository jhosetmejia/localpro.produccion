@extends('layouts.app')

@section('title', 'Elija sus páginas')

@push('css')
<style>
.eg label{display:flex;gap:.9rem;align-items:center;border:2px solid #e3ebe6;border-radius:18px;padding:.8rem 1rem;margin-bottom:.6rem;cursor:pointer}
.eg label:has(input:checked){border-color:#16b34a;background:#f4fcf6}
.eg input{width:22px;height:22px;accent-color:#16b34a;flex:none}
.eg img,.eg .ini{width:48px;height:48px;border-radius:14px;object-fit:cover;background:#e8eee9;display:grid;place-items:center;flex:none}
.eg b{display:block}.eg small{color:#6b7570;font-weight:600}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-brands fa-facebook me-1"></i>Conectar</small><h1>¿Qué páginas <span>conecta?</span></h1>
            <span class="text-muted fw-semibold">Elija la página de su negocio. Si tiene Instagram vinculado, se conecta con ella.</span></div>
    </div>
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('redes.elegir.guardar') }}" class="lp-box eg">
        @csrf
        @foreach ($paginas as $p)
        <label>
            <input type="checkbox" name="paginas[]" value="{{ $p['id'] }}" @checked(count($paginas) === 1 || in_array($p['id'], $ya, true))>
            @if ($p['foto'])<img src="{{ $p['foto'] }}" alt="">@else<span class="ini"><i class="fa-brands fa-facebook-f"></i></span>@endif
            <div><b>{{ $p['nombre'] }}</b>
                <small>@if ($p['ig_usuario'])<i class="fa-brands fa-instagram me-1" style="color:#e91e63"></i>Instagram: {{ '@' . $p['ig_usuario'] }}@else<span style="color:#a86b00">Sin Instagram profesional vinculado (solo Facebook)</span>@endif
                {{ in_array($p['id'], $ya, true) ? ' · ya conectada' : '' }}</small></div>
        </label>
        @endforeach
        <button class="btn btn-primary btn-lg w-100 mt-2"><i class="fa-solid fa-link me-1"></i>Conectar</button>
    </form>
</div>
@endsection
