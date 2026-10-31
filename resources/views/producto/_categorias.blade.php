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
        <div id="lpNuevaCatAyuda" class="lp-cat-ayuda" style="display:none"></div>
        <small class="text-danger d-block mt-1" id="lpNuevaCatError"></small>
    </div>
    <style>
    .lp-cat-ayuda{margin-top:.5rem;border-radius:14px;padding:.6rem .8rem;font-size:.84rem;font-weight:600;background:#f2fbf3;border:1.5px solid #cdeed5;color:#1d2a21}
    .lp-cat-ayuda.ya{background:#fff8e6;border-color:#ffd77a}
    .lp-cat-ayuda b{font-weight:900}
    .lp-cat-ayuda .sug{display:flex;flex-wrap:wrap;gap:6px;margin-top:.4rem}
    .lp-cat-ayuda .sug button{border:1.5px solid #16b34a;background:#fff;color:#11702f;border-radius:999px;padding:.25rem .75rem;font-weight:800;font-size:.8rem}
    .lp-cat-ayuda .sug button:hover{background:#16b34a;color:#fff}
    </style>
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

    var ayuda = document.getElementById('lpNuevaCatAyuda'), igual = null;

    // ---- Ayuda para no repetir categorías y dejarlas parejas ----
    // Igual que el servidor (Textos::titulo y Textos::clave): "GORRAS" → "Gorras"; "Gorra" = "gorras"
    var MENORES = ['de','del','la','las','el','los','y','e','o','u','a','al','en','para','por','con','sin','su','sus','tu','un','una','x'];
    function titulo(t) {
        t = (t || '').replace(/\s+/g, ' ').trim();
        return t.split(' ').map(function (p, i) {
            var min = p.toLowerCase(), letras = p.replace(/[^\p{L}]/gu, '');
            if (i > 0 && MENORES.indexOf(min) !== -1) return min;
            if (letras && letras.length <= 3 && letras === letras.toUpperCase() && MENORES.indexOf(min) === -1) return p;
            if (/\p{Ll}\p{Lu}/u.test(p) && p !== p.toUpperCase()) return p;
            return min.replace(/(^|[-\/(])(\p{L})/gu, function (m, a, b) { return a + b.toUpperCase(); });
        }).join(' ');
    }
    function clave(t) {
        t = (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9 ]+/g, ' ');
        return t.split(' ').filter(Boolean).map(function (p) {
            if (p.length > 4 && /[^aeiou]es$/.test(p)) return p.slice(0, -2);
            if (p.length > 3 && /s$/.test(p)) return p.slice(0, -1);
            return p;
        }).join(' ');
    }
    function distancia(a, b) {
        if (Math.abs(a.length - b.length) > 2) return 9;
        var d = [], i, j;
        for (i = 0; i <= a.length; i++) d[i] = [i];
        for (j = 0; j <= b.length; j++) d[0][j] = j;
        for (i = 1; i <= a.length; i++) for (j = 1; j <= b.length; j++)
            d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
        return d[a.length][b.length];
    }
    function existentes() {
        return Array.prototype.map.call(chips.querySelectorAll('input.btn-check'), function (c) {
            var l = chips.querySelector('label[for="' + c.id + '"]');
            var n = l ? l.textContent.trim() : '';
            return { input: c, nombre: n, clave: clave(n) };
        });
    }
    function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    function cerrar() { box.style.display = 'none'; btn.style.display = ''; inp.value = ''; err.textContent = ''; revisar(); }
    function usar(c, ya) {
        c.input.checked = true;
        cerrar();
        var aviso = document.getElementById('lpNuevaCatAviso');
        aviso.textContent = (ya ? 'Ya tenía la categoría "' : 'Listo, quedó elegida "') + c.nombre + (ya ? '": se reutilizó y quedó elegida.' : '".');
        aviso.style.display = 'block'; setTimeout(function () { aviso.style.display = 'none'; }, 5000);
    }
    function revisar() {
        var bruto = inp.value.trim(), k = clave(bruto);
        igual = null; ayuda.innerHTML = ''; ayuda.className = 'lp-cat-ayuda'; guardar.textContent = 'Crear';
        if (!k) { ayuda.style.display = 'none'; return; }
        var lista = existentes(), bonito = titulo(bruto);
        igual = lista.find(function (c) { return c.clave === k; }) || null;
        if (igual) {
            ayuda.className = 'lp-cat-ayuda ya';
            ayuda.innerHTML = '⚠️ Ya tiene la categoría <b>' + esc(igual.nombre) + '</b>. Reutilícela para no tener dos iguales.'
                + '<div class="sug"><button type="button" data-usar="' + igual.input.id + '">✓ Usar "' + esc(igual.nombre) + '"</button></div>';
            guardar.textContent = 'Usar esa';
            ayuda.style.display = '';
            return;
        }
        // Parecidas: una contiene a la otra ("Gorra" y "Gorra Plana") o casi se escriben igual ("Chaketa")
        var parecidas = lista.filter(function (c) {
            var a = ' ' + c.clave + ' ', b = ' ' + k + ' ';
            return (k.length >= 3 && (a.indexOf(b) !== -1 || b.indexOf(a) !== -1)) || (k.length >= 4 && distancia(c.clave, k) <= 2);
        }).slice(0, 6);
        var html = '';
        if (bonito !== bruto) html += 'Así quedará escrita: <b>' + esc(bonito) + '</b>';
        if (parecidas.length) {
            html += (html ? '<br>' : '') + '¿Es alguna de estas? Tóquela para usarla; si es otra distinta, toque <b>Crear</b>.'
                + '<div class="sug">' + parecidas.map(function (c) { return '<button type="button" data-usar="' + c.input.id + '">' + esc(c.nombre) + '</button>'; }).join('') + '</div>';
            guardar.textContent = 'Crear de todas formas';
        }
        ayuda.innerHTML = html;
        ayuda.style.display = html ? '' : 'none';
    }
    ayuda.addEventListener('click', function (e) {
        var b = e.target.closest('[data-usar]'); if (!b) return;
        var c = existentes().find(function (x) { return x.input.id === b.dataset.usar; });
        if (c) usar(c, c === igual);
    });
    inp.addEventListener('input', revisar);

    btn.addEventListener('click', function () { box.style.display = ''; btn.style.display = 'none'; inp.focus(); });
    document.getElementById('lpNuevaCatCancelar').addEventListener('click', cerrar);
    inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); crear(); } });
    guardar.addEventListener('click', crear);

    function crear() {
        var nombre = titulo(inp.value.trim());
        if (!nombre) { inp.focus(); return; }
        // Ya existe una igual: se reutiliza sin crearla otra vez
        revisar();
        if (igual) { usar(igual, true); return; }
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
            cerrar();
        }).catch(function () { err.textContent = 'Sin conexión. Intente de nuevo.'; })
          .finally(function () { guardar.disabled = false; });
    }
})();
</script>
@endpush
@endcan
