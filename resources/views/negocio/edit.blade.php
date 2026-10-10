@extends('layouts.app')

@section('title', 'Datos del negocio')

@section('content')
<div class="lp-dash">
    <div class="lp-hello"><div><small>Configuración</small><h1>Datos del <span>negocio</span></h1><span class="text-muted fw-semibold">Aparecen en el tiquete, la factura y el mensaje de WhatsApp.</span></div></div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('negocio.update') }}" enctype="multipart/form-data" class="lp-grid" style="grid-template-columns:1.4fr 1fr">
        @csrf @method('PUT')
        <div class="lp-box">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label">Nombre del negocio</label><input name="nombre" class="form-control" value="{{ old('nombre', $negocio->nombre) }}" required maxlength="80"></div>
                <div class="col-md-4"><label class="form-label">NIT</label><input name="nit" class="form-control" value="{{ old('nit', $negocio->nit) }}" maxlength="30"></div>
                <div class="col-md-8"><label class="form-label">Dirección</label><input name="direccion" class="form-control" value="{{ old('direccion', $negocio->direccion) }}" maxlength="120"></div>
                <div class="col-md-4"><label class="form-label">Ciudad</label><input name="ciudad" class="form-control" value="{{ old('ciudad', $negocio->ciudad) }}" maxlength="60" placeholder="Pasto"></div>
                <div class="col-md-6"><label class="form-label">Teléfono / WhatsApp</label><input name="telefono" class="form-control" value="{{ old('telefono', $negocio->telefono) }}" maxlength="30"></div>
                <div class="col-md-6"><label class="form-label">Correo</label><input name="email" type="email" class="form-control" value="{{ old('email', $negocio->email) }}" maxlength="120"></div>
                <div class="col-12"><label class="form-label">Mensaje al pie del tiquete</label><input name="mensaje_tiquete" class="form-control" value="{{ old('mensaje_tiquete', $negocio->mensaje_tiquete) }}" maxlength="255"></div>
            </div>
        </div>
        <div class="lp-box text-center">
            <h3>Logo</h3>
            <div style="width:160px;height:160px;margin:0 auto 1rem;border-radius:24px;border:2px dashed var(--lp-line);display:grid;place-items:center;overflow:hidden;background:#fafdfb">
                @if ($negocio->logo_url)
                    <img id="logoPrev" src="{{ $negocio->logo_url }}" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain">
                @else
                    <img id="logoPrev" alt="" style="max-width:100%;max-height:100%;object-fit:contain;display:none"><i id="logoIcon" class="fa-regular fa-image fa-3x" style="color:#bfeccb"></i>
                @endif
            </div>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control mb-2" onchange="var f=this.files[0];if(f){var i=document.getElementById('logoPrev');i.src=URL.createObjectURL(f);i.style.display='';var c=document.getElementById('logoIcon');if(c)c.style.display='none';}">
            <small class="text-muted d-block mb-2">PNG o JPG, máximo 2 MB. Mejor en blanco y negro para la impresora térmica.</small>
            @if ($negocio->logo_url)
            <div class="form-check d-inline-block"><input class="form-check-input" type="checkbox" name="quitar_logo" value="1" id="ql"><label class="form-check-label" for="ql">Quitar logo</label></div>
            @endif
            <button class="btn btn-primary w-100 mt-3"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios</button>
        </div>
    </form>
</div>
@endsection
