{{-- Categorías como botones para tocar (funciona bien en celular). Si no elige ninguna, queda en "General".
     La categoría nueva se crea aquí mismo, sin salir del formulario del producto. --}}
@php $elegidas = collect(old('categorias', $seleccionadas ?? []))->map(fn ($x) => (int) $x)->all(); @endphp
<div class="col-12">
    <label class="form-label">Categorías <span class="text-muted fw-normal">(toque para elegir una o varias)</span></label>
    <div class="lp-chips" id="lpChips">
        @foreach ($categorias as $item)
        <input type="checkbox" class="btn-check" name="categorias[]" id="cat{{ $item->id }}" value="{{ $item->id }}" autocomplete="off" @checked(in_array((int) $item->id, $elegidas, true))>
        <label class="lp-chip" for="cat{{ $item->id }}"><i class="fa-solid fa-check"></i> {{ $item->nombre }}</label>
        @endforeach
        @can('crear-categoria')
        <button type="button" class="lp-chip lp-chip-nueva" id="lpNuevaCat"><i class="fa-solid fa-plus"></i> Nueva categoría</button>
        @endcan
    </div>
    <div id="lpNuevaCatBox" class="mt-2" style="display:none">
        <div class="d-flex gap-2">
            <input type="text" class="form-control" id="lpNuevaCatNombre" maxlength="60" placeholder="Nombre de la categoría (ej. Chaquetas)">
            <button type="button" class="btn btn-primary" id="lpNuevaCatGuardar">Crear</button>
            <button type="button" class="btn btn-dark" id="lpNuevaCatCancelar" aria-label="Cancelar">&times;</button>
        </div>
        <small class="text-danger d-block mt-1" id="lpNuevaCatError"></small>
    </div>
    <small class="mt-1 fw-bold" id="lpNuevaCatAviso" style="display:none;color:#11702f"></small>
    <small class="text-muted">Si no elige ninguna, el producto queda en "General".</small>
    @if (\App\Support\TiendaActual::tienda()?->usa_tallas)
    @php $paraSel = old('para', $para ?? ''); @endphp
    <label class="form-label mt-3 mb-1">¿Para quién es? <span class="text-muted fw-normal">(así sus clientes lo encuentran en el catálogo)</span></label>
    <div class="lp-chips" id="lpPara">
        @foreach (\App\Models\Producto::PARA + ['todos' => ['👪', 'Para todos']] as $k => [$ico, $nom])
        <input type="radio" class="btn-check" name="para" id="para-{{ $k }}" value="{{ $k }}" autocomplete="off" @checked($paraSel === $k)>
        <label class="lp-chip" for="para-{{ $k }}">{{ $ico }} {{ $nom }}</label>
        @endforeach
    </div>
    <script>
    // Mientras no elija, se marca solo según el nombre ("Chaqueta dama" → Mujer)
    (function () {
        var caja = document.getElementById('lpPara'), nombre = document.getElementById('nombre'), tocado = !!caja.querySelector(':checked');
        if (!nombre) return;
        caja.addEventListener('click', function (e) { if (e.target.closest('label,input')) tocado = true; });
        function adivinar() {
            if (tocado) return;
            var t = ' ' + nombre.value.toLowerCase() + ' ', k = '';
            if (/[^a-zñ](niñ[oa]s?|nin[oa]s?|infantil|beb[eé]s?)[^a-zñ]/.test(t)) k = 'ninos';
            else if (/[^a-zñ](damas?|mujer(es)?|femenin[oa]s?)[^a-zñ]/.test(t)) k = 'mujer';
            else if (/[^a-zñ](caballeros?|hombres?|masculin[oa]s?)[^a-zñ]/.test(t)) k = 'hombre';
            caja.querySelectorAll('input').forEach(function (r) { r.checked = r.value === k; });
        }
        nombre.addEventListener('input', adivinar);
    })();
    </script>
    @endif
    @error('categorias')
    <small class="text-danger d-block">{{'*'.$message}}</small>
    @enderror
</div>

@can('crear-categoria')
@push('js')
<script>
(function () {
    var btn = document.getElementById('lpNuevaCat'), box = document.getElementById('lpNuevaCatBox');
    var inp = document.getElementById('lpNuevaCatNombre'), err = document.getElementById('lpNuevaCatError');
    var guardar = document.getElementById('lpNuevaCatGuardar'), chips = document.getElementById('lpChips');
    if (!btn) return;
    var token = document.querySelector('input[name=_token]').value;

    btn.addEventListener('click', function () { box.style.display = ''; btn.style.display = 'none'; inp.focus(); });
    document.getElementById('lpNuevaCatCancelar').addEventListener('click', function () { box.style.display = 'none'; btn.style.display = ''; inp.value = ''; err.textContent = ''; });
    inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); crear(); } });
    guardar.addEventListener('click', crear);

    function crear() {
        var nombre = inp.value.trim();
        if (!nombre) { inp.focus(); return; }
        guardar.disabled = true; err.textContent = '';
        fetch(@json(route('categorias.store')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ nombre: nombre })
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
          .then(function (res) {
            if (!res.ok) {
                var m = res.d.message || 'No se pudo crear.';
                if (res.d.errors && res.d.errors.nombre) m = res.d.errors.nombre[0];
                if (/taken|already|ya ha sido/i.test(m)) m = 'Ya existe una categoría con ese nombre.';
                err.textContent = m; return;
            }
            // Ya existía (ej. "Chaquetas" y "CHAQUETA"): se elige esa en vez de repetirla
            var ya = document.getElementById('cat' + res.d.id);
            if (ya) {
                ya.checked = true;
                inp.value = ''; box.style.display = 'none'; btn.style.display = '';
                err.textContent = '';
                var aviso = document.getElementById('lpNuevaCatAviso');
                aviso.textContent = 'Ya tenía la categoría "' + res.d.nombre + '": quedó elegida.';
                aviso.style.display = "block"; setTimeout(function () { aviso.style.display = 'none'; }, 5000);
                return;
            }
            // Se agrega como botón ya elegido, antes de "Nueva categoría"
            var c = document.createElement('input');
            c.type = 'checkbox'; c.className = 'btn-check'; c.name = 'categorias[]'; c.id = 'cat' + res.d.id; c.value = res.d.id; c.checked = true;
            var l = document.createElement('label');
            l.className = 'lp-chip'; l.htmlFor = c.id; l.innerHTML = '<i class="fa-solid fa-check"></i> ';
            l.appendChild(document.createTextNode(res.d.nombre));
            chips.insertBefore(c, btn); chips.insertBefore(l, btn);
            inp.value = ''; box.style.display = 'none'; btn.style.display = '';
        }).catch(function () { err.textContent = 'Sin conexión. Intente de nuevo.'; })
          .finally(function () { guardar.disabled = false; });
    }
})();
</script>
@endpush
@endcan
