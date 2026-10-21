@extends('layouts.app')

@section('title', $obituario->exists ? 'Editar obituario' : 'Nuevo obituario')

@push('css')
<style>
.of-prev{position:sticky;top:90px}
.of-prev [data-obi-marco]{border-radius:14px;box-shadow:0 20px 50px -30px rgba(0,0,0,.6);background:#111}
.of-foto{width:96px;height:96px;border-radius:50%;overflow:hidden;background:#eef2f0;display:grid;place-items:center;color:#9aa8a0;font-size:2rem;flex:0 0 96px}
.of-foto img{width:100%;height:100%;object-fit:cover}
.of-ayuda{font-size:.8rem;color:#6b7570}
.of-chips{display:flex;flex-wrap:wrap;gap:.4rem;align-items:center}
.of-chips input[type=radio]{display:none}
.of-chips label{border:1.5px solid #e3efe5;border-radius:999px;padding:.35rem .8rem;cursor:pointer;font-size:.85rem;font-weight:600;margin:0}
.of-chips input:checked + label{border-color:#2fd15a;background:#f4fcf6}
.of-pregunta{border:2px solid #2fd15a;background:#f4fcf6;border-radius:16px;padding:.8rem 1rem;margin-bottom:1rem}
.of-pregunta .btn{margin:.2rem .2rem 0 0}
input[type=color].of-color{width:44px;height:34px;padding:2px;border-radius:10px}
.of-ajuste{border:1.5px solid #e3efe5;border-radius:16px;padding:.8rem 1rem;background:#fbfdfb}
.aj-fila{display:flex;align-items:center;gap:.45rem;margin-top:.45rem;flex-wrap:wrap}
.aj-fila .form-range{flex:1;min-width:120px}
.aj-et{width:62px;font-size:.8rem;font-weight:700;color:#3b4540}
.aj-b{width:34px;height:34px;border-radius:10px;border:1.5px solid #e3efe5;background:#fff;display:grid;place-items:center;font-size:.85rem;color:#151a17;touch-action:manipulation;user-select:none}
.aj-b:hover{border-color:#2fd15a;background:#f4fcf6}
.aj-val{min-width:46px;text-align:right;font-size:.8rem;font-weight:700;color:#16b34a}
.aj-pad{display:grid;grid-template-columns:repeat(3,34px);gap:4px}
.aj-centro{color:#16b34a}
[data-obi-marco] .obi-foto[data-aj]{cursor:grab;touch-action:none}
[data-obi-marco] .obi-foto.aj-arrastrando{cursor:grabbing}
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
                            <input type="file" class="form-control" name="foto" id="of-foto" accept="image/*" data-foto-lista>
                            <script src="{{ asset('js/foto-lista.js') }}?v=2"></script>
                            <div class="of-ayuda mt-1">Mejor una foto donde se vea bien la cara. Máximo 8 MB. Después la puede girar, mover y acercar.</div>
                            <input type="hidden" name="foto_giro" id="of-giro" value="0">
                            @if ($obituario->foto_url)<div class="form-check small mt-1"><input class="form-check-input" type="checkbox" name="quitar_foto" value="1" id="qf"><label class="form-check-label" for="qf">Quitar foto</label></div>@endif
                        </div>
                    </div>
                    <div class="of-pregunta" id="of-pregunta" hidden>
                        <b><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Esta foto no tiene fondo. ¿Cómo la quiere?</b>
                        <div class="mt-1">
                            <button type="button" class="btn btn-sm btn-light" data-elige="sin">Sin fondo (se ve la tarjeta)</button>
                            <button type="button" class="btn btn-sm btn-light" data-elige="suave">Con fondo suave</button>
                            <button type="button" class="btn btn-sm btn-light" data-elige="color">Con un color</button>
                        </div>
                    </div>
                    <div class="of-pregunta" id="of-con-fondo" hidden style="border-color:#e3efe5;background:#fafafa">
                        <span class="small">¿Quiere la foto <b>sin fondo</b>? Quítele el fondo gratis en <a href="https://www.remove.bg/es" target="_blank" rel="noopener">remove.bg</a>, descárguela en PNG y súbala aquí. Le preguntamos qué poner detrás.</span>
                    </div>
                    @php $ff = old('foto_fondo', $obituario->foto_fondo ?: 'suave'); $fo = old('foto_forma', $obituario->foto_forma ?: 'circulo'); @endphp
                    <label class="form-label small fw-bold">Detrás de la foto</label>
                    <div class="of-chips mb-2">
                        @foreach (\App\Models\Obituario::FOTO_FONDOS as $k => [$nom, $ay])
                        <input type="radio" name="foto_fondo" id="ff-{{ $k }}" value="{{ $k }}" @checked($ff === $k)><label for="ff-{{ $k }}" title="{{ $ay }}">{{ $nom }}</label>
                        @endforeach
                        <input type="color" class="of-color" name="foto_color" id="of-fcolor" value="{{ old('foto_color', $obituario->foto_color ?: '#ffffff') }}" title="Color detrás de la foto">
                    </div>
                    <label class="form-label small fw-bold">Forma de la foto</label>
                    <div class="of-chips mb-3">
                        @foreach (\App\Models\Obituario::FOTO_FORMAS as $k => $nom)
                        <input type="radio" name="foto_forma" id="fo-{{ $k }}" value="{{ $k }}" @checked($fo === $k)><label for="fo-{{ $k }}">{{ $nom }}</label>
                        @endforeach
                    </div>
                    @php $aj = \App\Models\Obituario::ajusteFoto($obituario->exists ? $obituario : null); @endphp
                    <div class="of-ajuste mb-3" id="of-ajuste" @if (!$obituario->foto_url) hidden @endif>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <b class="small"><i class="fa-solid fa-sliders me-1"></i>Ajustar la foto</b>
                            <button type="button" class="btn btn-sm btn-light" id="aj-reset"><i class="fa-solid fa-arrow-rotate-left me-1"></i>Como estaba</button>
                        </div>
                        <div class="aj-fila">
                            <span class="aj-et">Girar</span>
                            <button type="button" class="aj-b" data-aj="r" data-paso="-1" title="Girar 1° a la izquierda"><i class="fa-solid fa-rotate-left"></i></button>
                            <input type="range" class="form-range" min="-180" max="180" step="1" id="aj-r" value="{{ old('foto_rot', $aj['r']) }}" aria-label="Girar">
                            <button type="button" class="aj-b" data-aj="r" data-paso="1" title="Girar 1° a la derecha"><i class="fa-solid fa-rotate-right"></i></button>
                            <output class="aj-val" id="aj-r-v"></output>
                        </div>
                        <div class="aj-fila">
                            <span class="aj-et">Tamaño</span>
                            <button type="button" class="aj-b" data-aj="z" data-paso="-2" title="Alejar"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                            <input type="range" class="form-range" min="50" max="300" step="1" id="aj-z" value="{{ old('foto_zoom', $aj['z']) }}" aria-label="Tamaño">
                            <button type="button" class="aj-b" data-aj="z" data-paso="2" title="Acercar"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                            <output class="aj-val" id="aj-z-v"></output>
                        </div>
                        <div class="aj-fila">
                            <span class="aj-et">Mover</span>
                            <div class="aj-pad">
                                <span></span><button type="button" class="aj-b" data-aj="y" data-paso="-1" title="Subir"><i class="fa-solid fa-arrow-up"></i></button><span></span>
                                <button type="button" class="aj-b" data-aj="x" data-paso="-1" title="A la izquierda"><i class="fa-solid fa-arrow-left"></i></button><button type="button" class="aj-b aj-centro" id="aj-centrar" title="Centrar"><i class="fa-solid fa-crosshairs"></i></button><button type="button" class="aj-b" data-aj="x" data-paso="1" title="A la derecha"><i class="fa-solid fa-arrow-right"></i></button>
                                <span></span><button type="button" class="aj-b" data-aj="y" data-paso="1" title="Bajar"><i class="fa-solid fa-arrow-down"></i></button><span></span>
                            </div>
                            <span class="of-ayuda ms-2">También puede <b>arrastrar la foto</b> con el dedo o el mouse en la vista previa. Deje oprimida una flecha para moverla seguido.</span>
                        </div>
                        <input type="hidden" name="foto_rot" id="aj-in-r" value="{{ old('foto_rot', $aj['r']) }}">
                        <input type="hidden" name="foto_zoom" id="aj-in-z" value="{{ old('foto_zoom', $aj['z']) }}">
                        <input type="hidden" name="foto_x" id="aj-in-x" value="{{ old('foto_x', $aj['x']) }}">
                        <input type="hidden" name="foto_y" id="aj-in-y" value="{{ old('foto_y', $aj['y']) }}">
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
<script src="{{ asset('js/obituarios.js') }}?v=9"></script>
<script>
(function () {
    var form = document.getElementById('of-form'), el = document.querySelector('[data-obi]');
    var CLAVE = 'obi-form-{{ $obituario->id ?: 'nuevo' }}';
    var fraseDefecto = @json($plantilla->frase ?: '');

    // Al cambiar de plantilla se recarga la página: se guarda TODO lo hecho (textos, opciones, ajustes y la foto
    // que subió) para que no tenga que volver a hacerlo. La foto se guarda en el navegador (IndexedDB).
    var BD = 'obi-form-fotos';
    function bd() {
        return new Promise(function (ok, mal) {
            if (!window.indexedDB) return mal();
            var r = indexedDB.open(BD, 1);
            r.onupgradeneeded = function () { r.result.createObjectStore('f'); };
            r.onsuccess = function () { ok(r.result); };
            r.onerror = mal;
        });
    }
    function fotoGuardar(file) {
        return bd().then(function (db) { return new Promise(function (ok) {
            var t = db.transaction('f', 'readwrite'); t.objectStore('f').put(file, CLAVE); t.oncomplete = ok; t.onerror = ok;
        }); }).catch(function () {});
    }
    function fotoSacar() {
        return bd().then(function (db) { return new Promise(function (ok) {
            var t = db.transaction('f', 'readwrite'), st = t.objectStore('f'), q = st.get(CLAVE);
            q.onsuccess = function () { st.delete(CLAVE); ok(q.result || null); };
            q.onerror = function () { ok(null); };
        }); }).catch(function () { return null; });
    }
    var fotoPendiente = null;
    try {
        var guardado = sessionStorage.getItem(CLAVE);
        if (guardado) {
            sessionStorage.removeItem(CLAVE);
            var dg = JSON.parse(guardado), lista = Array.isArray(dg) ? dg : dg.campos;
            lista.forEach(function (c) { var f = form.elements[c[0]]; if (f && f.type !== 'file' && c[0] !== 'plantilla_id' && c[0] !== '_token' && c[0] !== '_method') f.value = c[1]; });
            if (dg.foto) fotoPendiente = fotoSacar();
        }
    } catch (e) {}
    document.getElementById('of-plantilla').addEventListener('change', function () {
        var sel = this, archivo = document.getElementById('of-foto').files[0];
        var datos = [];
        [].forEach.call(form.elements, function (f) {
            if (!f.name || f.type === 'file' || f.type === 'checkbox') return;
            if (f.type === 'radio' && !f.checked) return;
            datos.push([f.name, f.value]);
        });
        (archivo ? fotoGuardar(archivo) : Promise.resolve()).then(function () {
            try { sessionStorage.setItem(CLAVE, JSON.stringify({ campos: datos, foto: !!archivo })); } catch (e) {}
            var u = new URL(location.href); u.searchParams.set('plantilla', sel.value); location.href = u.toString();
        });
    });

    function anio(v) { return v ? v.slice(8, 10) + '-' + v.slice(5, 7) + '-' + v.slice(0, 4) : ''; }
    function vivo() {
        var f = form.elements, sala = f.sala_id.options[f.sala_id.selectedIndex];
        var a1 = anio(f.nacimiento.value), a2 = anio(f.fallecimiento.value);
        Obi.datos(el, {
            nombre: f.nombre.value || 'Nombre del fallecido',
            anios: (a1 || a2) ? (a1 + ' / ' + a2).replace(/^ \/ | \/ $/g, '') : '',
            frase: f.frase.value || fraseDefecto,
            sala: f.sala_id.value ? sala.text : '',
            fecha: Obi.fecha(f.fecha_exequias.value),
            hora: Obi.hora(f.hora_exequias.value),
            lugar: f.lugar_exequias.value,
            destino: f.destino_final.value,
            invitan: f.invitan.value,
            foto_fondo: (form.querySelector('[name=foto_fondo]:checked') || {}).value,
            foto_color: f.foto_color.value,
            foto_forma: (form.querySelector('[name=foto_forma]:checked') || {}).value
        });
        document.getElementById('of-fcolor').style.display = (form.querySelector('[name=foto_fondo]:checked') || {}).value === 'color' ? '' : 'none';
    }
    form.addEventListener('input', vivo);
    form.addEventListener('change', function (e) { if (e.target.id !== 'of-foto') vivo(); });
    // ===== Foto: mostrar, girar de a 1°, mover y acercar (se guarda como ajuste, la foto original no se toca) =====
    var qf = document.getElementById('qf'), panel = document.getElementById('of-ajuste');
    var aj = {
        r: +document.getElementById('aj-in-r').value || 0,
        z: +document.getElementById('aj-in-z').value || 100,
        x: +document.getElementById('aj-in-x').value || 0,
        y: +document.getElementById('aj-in-y').value || 0
    };
    var LIM = { r: [-180, 180], z: [50, 300], x: [-100, 100], y: [-100, 100] };
    function aplicar() {
        ['r', 'z', 'x', 'y'].forEach(function (k) {
            aj[k] = Math.round(Math.max(LIM[k][0], Math.min(LIM[k][1], aj[k])));
            document.getElementById('aj-in-' + k).value = aj[k];
        });
        document.getElementById('aj-r').value = aj.r;
        document.getElementById('aj-z').value = aj.z;
        document.getElementById('aj-r-v').textContent = aj.r + '°';
        document.getElementById('aj-z-v').textContent = aj.z + '%';
        el.querySelectorAll('.obi-foto').forEach(function (fb) {
            fb.style.setProperty('--pr', aj.r + 'deg'); fb.style.setProperty('--px', aj.x + '%');
            fb.style.setProperty('--py', aj.y + '%'); fb.style.setProperty('--pz', (aj.z / 100) + '');
        });
        var mini = document.querySelector('#of-foto-prev img');
        if (mini) mini.style.transform = 'translate(' + aj.x + '%,' + aj.y + '%) rotate(' + aj.r + 'deg) scale(' + (aj.z / 100) + ')';
    }
    function mostrarFoto(url) {
        document.getElementById('of-foto-prev').innerHTML = '<img src="' + url + '" alt="">';
        Obi.datos(el, { foto: url });
        panel.hidden = false;
        aplicar();
        arrastrable();
    }
    document.getElementById('aj-r').addEventListener('input', function () { aj.r = +this.value; aplicar(); });
    document.getElementById('aj-z').addEventListener('input', function () { aj.z = +this.value; aplicar(); });
    // Botones: un toque mueve poquito; dejándolo oprimido sigue moviendo
    document.querySelectorAll('[data-aj]').forEach(function (b) {
        var t1, t2;
        function paso() { aj[b.dataset.aj] += +b.dataset.paso; aplicar(); }
        function parar() { clearTimeout(t1); clearInterval(t2); }
        b.addEventListener('pointerdown', function (e) { e.preventDefault(); paso(); t1 = setTimeout(function () { t2 = setInterval(paso, 70); }, 400); });
        ['pointerup', 'pointerleave', 'pointercancel'].forEach(function (ev) { b.addEventListener(ev, parar); });
        b.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); paso(); } });
    });
    document.getElementById('aj-centrar').addEventListener('click', function () { aj.x = 0; aj.y = 0; aplicar(); });
    document.getElementById('aj-reset').addEventListener('click', function () { aj = { r: 0, z: 100, x: 0, y: 0 }; aplicar(); });
    // Arrastrar la foto en la vista previa
    function arrastrable() {
        el.querySelectorAll('.obi-foto').forEach(function (fb) {
            if (fb.dataset.aj) return;
            fb.dataset.aj = '1';
            var ini = null;
            fb.addEventListener('pointerdown', function (e) {
                if (!fb.querySelector('img')) return;
                var r = fb.getBoundingClientRect();
                ini = { px: e.clientX, py: e.clientY, x: aj.x, y: aj.y, w: r.width || 1, h: r.height || 1 };
                fb.setPointerCapture(e.pointerId); fb.classList.add('aj-arrastrando'); e.preventDefault();
            });
            fb.addEventListener('pointermove', function (e) {
                if (!ini) return;
                aj.x = ini.x + (e.clientX - ini.px) / ini.w * 100;
                aj.y = ini.y + (e.clientY - ini.py) / ini.h * 100;
                aplicar();
            });
            function soltar() { ini = null; fb.classList.remove('aj-arrastrando'); }
            fb.addEventListener('pointerup', soltar); fb.addEventListener('pointercancel', soltar);
        });
    }
    function fotoNueva(file) {
        aj = { r: 0, z: 100, x: 0, y: 0 };
        if (qf) { qf.checked = false; }
        var url = URL.createObjectURL(file);
        mostrarFoto(url);
        // PNG sin fondo: se le pregunta qué poner detrás. Con fondo: se le dice cómo quitarlo.
        Obi.tieneTransparencia(url).then(function (transp) {
            document.getElementById('of-pregunta').hidden = !transp;
            document.getElementById('of-con-fondo').hidden = transp;
        });
    }
    document.getElementById('of-foto').addEventListener('change', function () {
        var file = this.files[0]; if (file) fotoNueva(file);
    });
    // Volvió de cambiar de plantilla: se pone otra vez la foto que había subido, con sus ajustes
    if (fotoPendiente) fotoPendiente.then(function (file) {
        if (!file) return;
        var input = document.getElementById('of-foto');
        try { var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; } catch (e) {}
        var guardados = { r: aj.r, z: aj.z, x: aj.x, y: aj.y };
        fotoNueva(file);
        aj = guardados; aplicar();
    });
    document.querySelectorAll('[data-elige]').forEach(function (b) {
        b.addEventListener('click', function () {
            var v = b.dataset.elige;
            document.getElementById('ff-' + v).checked = true;
            // Sin fondo se ve mejor como recorte libre (la persona sobre la tarjeta)
            if (v === 'sin') document.getElementById('fo-libre').checked = true;
            document.getElementById('of-pregunta').hidden = true;
            vivo();
            if (v === 'color') document.getElementById('of-fcolor').click();
        });
    });
    if (qf) qf.addEventListener('change', function () {
        panel.hidden = qf.checked;
        if (qf.checked) Obi.datos(el, { foto: '' }); else Obi.datos(el, { foto: @json($obituario->foto_url) });
    });
    aplicar();
    arrastrable();
    vivo();
})();
</script>
@endpush
