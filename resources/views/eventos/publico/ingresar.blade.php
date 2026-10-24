@extends('eventos.publico._layout')

@section('titulo', 'Ingresar · Mis entradas')
@section('meta')<meta name="robots" content="noindex, nofollow">@endsection

@section('content')
<form method="post" action="{{ route('mis-entradas.login') }}" class="card" style="margin-top:24px">
    @csrf
    @if ($evento)<input type="hidden" name="e" value="{{ $evento->slug }}">@endif
    <h2><i class="fa-solid fa-ticket" style="color:var(--c)"></i> Mis entradas</h2>
    <p class="mu">Ingresa con el correo y la clave que creaste al registrarte.</p>
    <label class="lb" for="email">Correo</label>
    <input class="in" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required>
    <label class="lb" for="clave">Clave</label>
    <input class="in" id="clave" type="password" name="password" autocomplete="current-password" required>
    <button class="btn btn-c" style="margin-top:20px"><i class="fa-solid fa-right-to-bracket"></i> Ingresar</button>
    <p class="mu" style="text-align:center;margin-top:14px;font-size:.88rem">¿Olvidaste la clave? Escríbele al organizador del evento: él te la puede restablecer.</p>
</form>
@if ($evento)
<a class="btn btn-l" href="{{ route('evento.comprar', $evento->slug) }}"><i class="fa-solid fa-user-plus"></i> Soy nuevo: registrarme</a>
@endif
@endsection
