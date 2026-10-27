@extends('layouts.app')

@section('title', 'Crear tienda')

@section('content')
<div class="lp-dash" style="max-width:860px;margin:0 auto">
    <div class="lp-hello"><div><small><a href="{{ route('admin.tiendas.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Tiendas</a></small><h1>Crear <span>tienda</span></h1><span class="text-muted fw-semibold">El dueño termina la configuración la primera vez que entre.</span></div></div>
    @include('layouts.partials.lp-flash')
    <form method="post" action="{{ route('admin.tiendas.store') }}">
        @csrf
        <div class="lp-box mb-3">
            <h3>Negocio</h3>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nombre del negocio</label><input name="nombre" class="form-control" value="{{ old('nombre') }}" required maxlength="80"></div>
                <div class="col-md-6"><label class="form-label">Tipo de negocio</label>
                    <select name="tipo_negocio" class="form-select" required data-buscador data-buscador-texto="Escriba para buscar el tipo de negocio…">
                        @foreach ($tipos as $k => $t)<option value="{{ $k }}" data-icono="{{ $t[1] }}" data-desc="{{ $t[3] ?? '' }}" @selected(old('tipo_negocio', 'moda') === $k)>{{ $t[0] }}</option>@endforeach
                    </select>
                </div>
                @include('layouts.partials.ubicacion', ['pais' => 'CO'])
                <div class="col-md-6"><label class="form-label">Teléfono de la tienda</label><input name="telefono" class="form-control" value="{{ old('telefono') }}" maxlength="30"></div>
            </div>
        </div>
        <div class="lp-box">
            <h3>Dueño (usuario de acceso)</h3>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nombre completo</label><input name="dueno_nombre" class="form-control" value="{{ old('dueno_nombre') }}" required maxlength="80"></div>
                <div class="col-md-6"><label class="form-label">Celular / WhatsApp</label><input name="dueno_telefono" class="form-control" value="{{ old('dueno_telefono') }}" maxlength="20"></div>
                <div class="col-md-6"><label class="form-label">Correo (usuario)</label><input type="email" name="dueno_email" class="form-control" value="{{ old('dueno_email') }}" required maxlength="120"></div>
                <div class="col-md-6"><label class="form-label">Contraseña inicial</label><input type="text" name="dueno_password" class="form-control" required minlength="8" autocomplete="new-password"><small class="text-muted">Mínimo 8 caracteres. Pídale que la cambie en "Mi perfil".</small></div>
            </div>
            <button class="btn btn-primary btn-lg w-100 mt-4"><i class="fa-solid fa-store me-1"></i>Crear tienda y dueño</button>
        </div>
    </form>
</div>
@endsection

@push('js')
<script src="{{ asset('js/select-buscador.js') }}?v=1"></script>
@endpush
