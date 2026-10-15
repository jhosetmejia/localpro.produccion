@extends('layouts.app')

@section('title', $obituario->exists ? 'Editar obituario' : 'Nuevo obituario')

@push('css')
<style>
.of-prev{position:sticky;top:90px}
.of-prev [data-obi-marco]{border-radius:14px;box-shadow:0 20px 50px -30px rgba(0,0,0,.6);background:#111}
.of-foto{width:96px;height:96px;border-radius:50%;overflow:hidden;background:#eef2f0;display:grid;place-items:center;color:#9aa8a0;font-size:2rem;flex:0 0 96px}
.of-foto img{width:100%;height:100%;object-fit:cover}
.of-ayuda{font-size:.8rem;color:#6b7570}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Obituarios</small><h1>{{ $obituario->exists ? 'Editar' : 'Nuevo' }} <span>obituario</span></h1></div>
        <div class="lp-quick"><a href="{{ route('obituarios.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-xl-5">
            <form method="post" enctype="multipart/form-data" id="of-form" action="{{ $obituario->exists ? route('obituarios.update', $obituario) : route('obituarios.store') }}">
                @csrf
                @if ($obituario->exists) @method('put') @endif

                <div class="lp-box mb-3">
                    <h3>1. Plantilla y sala</h3>
                    <div class="row g-2">
                        <div class="col-sm-7">
                            <label class="form-label small fw-bold">Plantilla (diseño)</label>
                            <select class="form-select" name="plantilla_id" id="of-plantilla">
                                @foreach ($plantillas as $pl)
                                <option value="{{ $pl->id }}" @selected($pl->id === $plantilla->id)>{{ $pl->nombre }} · {{ \App\Models\ObituarioPlantilla::DISENOS[$pl->diseno][0] ?? '' }}</option>
                                @endforeach
                            </select>
                            <div class="of-ayuda mt-1"><a href="{{ route('obituarios.plantillas.edit', $plantilla) }}">Editar esta plantilla</a> · <a href="{{ route('obituarios.plantillas.create') }}">Crear otra</a></div>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label small fw-bold">Sala de velación</label>
                            <select class="form-select" name="sala_id" id="of-sala">
                                <option value="">Sin sala</option>
                                @foreach ($salas as $s)
                                <option value="{{ $s->id }}" @selected((string) old('sala_id', $obituario->sala_id) === (string) $s->id)>{{ $s->nombre }}</option>
                                @endforeach
                            </select>
                            @if ($salas->isEmpty())<div class="of-ayuda mt-1"><a href="{{ route('obituarios.index') }}">Cree sus salas</a> para que salga en el televisor.</div>@endif
                        </div>
                    </div>
                </div>

                <div class="lp-box mb-3">
                    <h3>2. El fallecido</h3>
                    <label class="form-label small fw-bold">Nombre completo</label>
                    <input class="form-control mb-3" name="nombre" data-c="nombre" maxlength="120" required value="{{ old('nombre', $obituario->nombre) }}" placeholder="Ej: Julio Hernán Ramos Rodríguez">
                    <div class="d-flex gap-3 align-items-center mb-3">
                        <div class="of-foto" id="of-foto-prev">@if ($obituario->foto_url)<img src="{{ $obituario->foto_url }}" alt="">@else<i class="fa-solid fa-user"></i>@endif</div>
                        <div class="flex-grow-1">
                            <label class="form-label small fw-bold">Foto</label>
                            <input type="file" class="form-control" name="foto" id="of-foto" accept="image/png,image/jpeg,image/webp">
                            <div class="of-ayuda mt-1">Mejor una foto donde se vea bien la cara (sale en círculo). Máximo 8 MB.</div>
                            @if ($obituario->foto_url)<div class="form-check small mt-1"><input class="form-check-input" type="checkbox" name="quitar_foto" value="1" id="qf"><label class="form-check-label" for="qf">Quitar foto</label></div>@endif
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small fw-bold">Nació <span class="text-muted fw-normal">(opcional)</span></label><input type="date" class="form-control" name="nacimiento" data-c="anios" value="{{ old('nacimiento', $obituario->nacimiento?->format('Y-m-d')) }}"></div>
                        <div class="col-6"><label class="form-label small fw-bold">Falleció <span class="text-muted fw-normal">(opcional)</span></label><input type="date" class="form-control" name="fallecimiento" data-c="anios" value="{{ old('fallecimiento', $obituario->fallecimiento?->format('Y-m-d')) }}"></div>
                    </div>
                    <label class="form-label small fw-bold mt-3">Frase</label>
                    <input class="form-control" name="frase" data-c="frase" maxlength="150" value="{{ old('frase', $obituario->frase) }}" placeholder="{{ $plantilla->frase ?: 'Descansó en la paz del Señor' }}">
                    <div class="of-ayuda mt-1">Vacía = la de la plantilla.</div>
                </div>

                <div class="lp-box mb-3">
                    <h3>3. Exequias</h3>
                    <div class="row g-2">
                        <div class="col-7"><label class="form-label small fw-bold">Fecha</label><input type="date" class="form-control" name="fecha_exequias" data-c="fecha" value="{{ old('fecha_exequias', $obituario->fecha_exequias?->format('Y-m-d')) }}"></div>
                        <div class="col-5"><label class="form-label small fw-bold">Hora</label><input type="time" class="form-control" name="hora_exequias" data-c="hora" value="{{ old('hora_exequias', $obituario->hora_exequias ? substr($obituario->hora_exequias, 0, 5) : '') }}"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Lugar de las exequias</label><input class="form-control" name="lugar_exequias" data-c="lugar" maxlength="150" value="{{ old('lugar_exequias', $obituario->lugar_exequias) }}" placeholder="Ej: Parroquia Nuestra Señora del Carmen"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Destino final</label><input class="form-control" name="destino_final" data-c="destino" maxlength="150" value="{{ old('destino_final', $obituario->destino_final) }}" placeholder="Ej: Cremación / Cementerio Central"></div>
                    </div>
                </div>

                <div class="lp-box mb-3">
                    <h3>4. Invitan</h3>
                    <textarea class="form-control" name="invitan" data-c="invitan" rows="7" maxlength="3000" placeholder="Su esposa: Patricia Trujillo&#10;Sus hijos: Cristian, Angie y Julieth&#10;Demás familiares y amigos">{{ old('invitan', $obituario->exists ? $obituario->invitan : \App\Models\Obituario::INVITAN_EJEMPLO) }}</textarea>
                    <div class="of-ayuda mt-1">Una línea por grupo. Escriba el grupo, dos puntos y los nombres: <b>Sus hijos: Cristian, Angie y Julieth</b>. Si son muchos, la letra se achica sola para que quepa.</div>
                </div>

                <div class="lp-box mb-3">
                    <h3>5. ¿Hasta cuándo sale en la pantalla?</h3>
                    <input type="datetime-local" class="form-control" name="mostrar_hasta" value="{{ old('mostrar_hasta', $obituario->mostrar_hasta?->format('Y-m-d\TH:i')) }}">
                    <div class="of-ayuda mt-1">Opcional. Después de esa hora sale de la pantalla solo. Si lo deja vacío, sale hasta que lo finalice.</div>
                </div>

                <button class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-floppy-disk me-1"></i>{{ $obituario->exists ? 'Guardar cambios' : 'Crear obituario y mostrar en la sala' }}</button>
            </form>
        </div>

        <div class="col-xl-7">
            <div class="of-prev">
                <div class="d-flex align-items-center mb-2"><b>Vista previa</b><small class="text-muted ms-2">{{ $plantilla->medida_datos['nombre'] }}</small></div>
                <div data-obi-marco>@include('obituarios._lienzo', ['p' => $plantilla, 'o' => $obituario->exists ? $obituario : null, 'tienda' => $tienda])</div>
                <div class="of-ayuda mt-2">Así se verá en el televisor. Las animaciones y el video también salen en la pantalla.</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/obituarios.js') }}?v=1"></script>
<script>
(function () {
    var form = document.getElementById('of-form'), el = document.querySelector('[data-obi]');
    var CLAVE = 'obi-form-{{ $obituario->id ?: 'nuevo' }}';
    var fraseDefecto = @json($plantilla->frase ?: '');

    // Al cambiar de plantilla se recarga la página: se guardan los datos escritos para no perderlos
    try {
        var guardado = sessionStorage.getItem(CLAVE);
        if (guardado) {
            sessionStorage.removeItem(CLAVE);
            JSON.parse(guardado).forEach(function (c) { var f = form.elements[c[0]]; if (f && f.type !== 'file' && c[0] !== 'plantilla_id' && c[0] !== '_token' && c[0] !== '_method') f.value = c[1]; });
        }
    } catch (e) {}
    document.getElementById('of-plantilla').addEventListener('change', function () {
        try {
            var datos = [];
            [].forEach.call(form.elements, function (f) { if (f.name && f.type !== 'file' && f.type !== 'checkbox') datos.push([f.name, f.value]); });
            sessionStorage.setItem(CLAVE, JSON.stringify(datos));
        } catch (e) {}
        var u = new URL(location.href); u.searchParams.set('plantilla', this.value); location.href = u.toString();
    });

    function anio(v) { return v ? v.slice(0, 4) : ''; }
    function vivo() {
        var f = form.elements, sala = f.sala_id.options[f.sala_id.selectedIndex];
        var a1 = anio(f.nacimiento.value), a2 = anio(f.fallecimiento.value);
        Obi.datos(el, {
            nombre: f.nombre.value || 'Nombre del fallecido',
            anios: (a1 || a2) ? (a1 + ' – ' + a2).replace(/^ – | – $/g, '') : '',
            frase: f.frase.value || fraseDefecto,
            sala: f.sala_id.value ? sala.text : '',
            fecha: Obi.fecha(f.fecha_exequias.value),
            hora: Obi.hora(f.hora_exequias.value),
            lugar: f.lugar_exequias.value,
            destino: f.destino_final.value,
            invitan: f.invitan.value
        });
    }
    form.addEventListener('input', vivo);
    form.addEventListener('change', function (e) { if (e.target.id !== 'of-foto') vivo(); });
    document.getElementById('of-foto').addEventListener('change', function () {
        var file = this.files[0]; if (!file) return;
        var url = URL.createObjectURL(file);
        document.getElementById('of-foto-prev').innerHTML = '<img src="' + url + '" alt="">';
        Obi.datos(el, { foto: url });
    });
    var qf = document.getElementById('qf');
    if (qf) qf.addEventListener('change', function () { Obi.datos(el, { foto: qf.checked ? '' : @json($obituario->foto_url) }); });
    vivo();
})();
</script>
@endpush
