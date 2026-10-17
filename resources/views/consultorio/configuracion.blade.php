@extends('layouts.app')

@section('title', 'Consultorio · configuración')

@push('css')
<style>
.cf label{font-weight:800;font-size:.8rem;color:var(--lp-muted);margin-bottom:.2rem;display:block}
.cf-firma{border:1.5px dashed #c9d3cd;border-radius:16px;padding:1rem;text-align:center;background:#fafcfb}
.cf-firma img{max-height:90px;max-width:100%}
.cf-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start}
.cf-leyes li{margin-bottom:.35rem}
@media (max-width:991px){.cf-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-user-doctor me-1"></i>Consultorio</small><h1>{{ $clinico ? 'Mi perfil' : 'Configuración' }} <span>profesional</span></h1></div>
    </div>
    @include('consultorio._nav')

    <form method="post" action="{{ route('consultorio.configuracion.guardar') }}" enctype="multipart/form-data" class="cf-grid cf">
        @csrf
        @if ($clinico)
        <div class="cs-card">
            <h3>Mis datos como profesional</h3>
            <p class="text-muted small">Salen en cada atención que firme, en las fórmulas y en las incapacidades.</p>
            <div class="mb-2"><label>Nombre</label><input class="form-control" value="{{ $u->name }}" disabled><small class="text-muted">Se cambia en Mi cuenta.</small></div>
            <div class="mb-2"><label>Especialidad o profesión</label><input name="especialidad" class="form-control" value="{{ old('especialidad', $u->especialidad) }}" maxlength="80" placeholder="Ej. Psicóloga clínica · Odontólogo · Fisioterapeuta"></div>
            <div class="mb-3"><label>Registro profesional (tarjeta profesional / RETHUS)</label><input name="registro_profesional" class="form-control" value="{{ old('registro_profesional', $u->registro_profesional) }}" maxlength="60"></div>
            <label>Firma</label>
            <div class="cf-firma mb-2">
                @if ($firma)<img src="{{ $firma }}" alt="Firma"><div class="mt-2"><label class="d-inline-flex gap-1 align-items-center" style="font-weight:700;color:#b0173f"><input type="checkbox" name="quitar_firma" value="1"> Quitar firma</label></div>
                @else<div class="text-muted small mb-2">Firme en una hoja blanca, tómele foto y súbala (mejor en PNG con fondo blanco).</div>@endif
                <input type="file" name="firma" accept="image/png,image/jpeg" class="form-control form-control-sm mt-2">
            </div>
            <small class="text-muted d-block mb-3"><i class="fa-solid fa-lock me-1"></i>La firma se guarda privada: no queda publicada en internet.</small>
        </div>
        @endif

        <div class="d-flex flex-column gap-3">
            @can('editar-negocio')
            <div class="cs-card">
                <h3>Del consultorio</h3>
                <div class="mb-2"><label>Tipo de historia por defecto</label>
                    <select name="plantilla" class="form-select">@foreach (\App\Support\Consultorio::plantillas() as $k => $pl)<option value="{{ $k }}" @selected($plantilla === $k)>{{ $pl['nombre'] }}</option>@endforeach</select></div>
                <div class="mb-2"><label>Duración de cada cita (minutos)</label><input type="number" name="cita_duracion" class="form-control" min="5" max="240" step="5" value="{{ $duracion }}"></div>
                <small class="text-muted">Para que otro usuario pueda ver y escribir historias, actívele el permiso <b>"Historia clínica"</b> en Usuarios. Recepción solo necesita "Agenda del consultorio".</small>
            </div>
            @endcan
            <div class="cs-card">
                <h3>Cómo cuida LocalPro la historia clínica</h3>
                <ul class="cf-leyes small m-0 ps-3">
                    <li><b>Cerrada no se cambia:</b> al cerrar una atención queda firmada con su nombre, registro y una huella de integridad. Las correcciones van como notas aclaratorias (Res. 1995 de 1999).</li>
                    <li><b>Reservada:</b> solo los usuarios con permiso de Historia clínica la ven. Recepción ve datos de contacto y la agenda.</li>
                    <li><b>Cifrada:</b> motivo, examen, diagnósticos, fórmulas, alergias y antecedentes se guardan cifrados.</li>
                    <li><b>Con registro de accesos:</b> el dueño ve quién abrió, imprimió o escribió en cada historia.</li>
                    <li><b>No se borra:</b> un paciente con historia no se puede eliminar.</li>
                    <li><b>Datos personales:</b> registre la autorización de tratamiento de datos de cada paciente (Ley 1581 de 2012).</li>
                </ul>
            </div>
        </div>
        <div style="grid-column:1/-1"><button class="cs-btn prim"><i class="fa-solid fa-floppy-disk"></i>Guardar</button></div>
    </form>
</div>
@endsection
