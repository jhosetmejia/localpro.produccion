@extends('layouts.app')

@section('title', 'Mi cuenta')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-user me-1"></i>{{ $u->es_superadmin ? 'Administrador LocalPro' : ($u->asesor ? $u->asesor->cargo_nombre : 'Usuario') }}</small><h1>Mi <span>cuenta</span></h1>
            <span class="text-muted fw-semibold">{{ $u->email }}</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('cuenta.update') }}" class="lp-grid">
        @csrf @method('PUT')
        <div class="lp-box">
            <h3>Mis datos</h3>
            <div class="mb-3"><label class="form-label">Nombre</label><input name="name" class="form-control" maxlength="80" required value="{{ old('name', $u->name) }}"></div>
            <div class="mb-3"><label class="form-label">Celular / WhatsApp</label><input name="telefono" class="form-control" maxlength="20" inputmode="tel" value="{{ old('telefono', $u->telefono) }}"></div>
            <div class="mb-3"><label class="form-label">Correo para entrar</label><input class="form-control" value="{{ $u->email }}" disabled></div>
        </div>
        <div class="lp-box">
            <h3>Cambiar clave</h3>
            <p class="text-muted small">Déjelo vacío si no quiere cambiarla.</p>
            <div class="mb-3"><label class="form-label">Clave actual</label><input type="password" name="clave_actual" class="form-control" autocomplete="current-password"></div>
            <div class="mb-3"><label class="form-label">Clave nueva</label><input type="password" name="clave" class="form-control" minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres"></div>
            <div class="mb-3"><label class="form-label">Repita la clave nueva</label><input type="password" name="clave_confirmation" class="form-control" autocomplete="new-password"></div>
        </div>
        <div style="grid-column:1/-1"><button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button></div>
    </form>
</div>
@endsection
