@extends('eventos.publico._layout')

@section('titulo', 'Comprar entrada · ' . $evento->nombre)
@section('meta')<meta name="robots" content="noindex, nofollow">@endsection

@section('css')
.res{display:flex;gap:.8rem;align-items:center}
.res img{width:64px;height:86px;object-fit:cover;border-radius:12px;flex:0 0 64px}
.dos{display:grid;grid-template-columns:120px 1fr;gap:.6rem}
@media (max-width:380px){.dos{grid-template-columns:1fr}}
.ojo{position:relative}.ojo button{position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:0;color:var(--mu);width:42px;height:42px;font-size:1rem;cursor:pointer}
@endsection

@section('content')
<div class="card res" style="margin-top:20px">
    @if ($evento->imagen_url)<img src="{{ $evento->imagen_url }}" alt="">@endif
    <div><small class="mu">{{ $evento->etapa ?: 'Entrada' }}</small><div style="font-weight:800;font-size:1.1rem">{{ $evento->nombre }}</div>
        <div style="font-weight:800;color:var(--c);font-size:1.2rem">{{ $evento->precio_texto }}</div></div>
</div>

<form method="post" action="{{ route('evento.registrar', $evento->slug) }}" class="card" id="ev-form" novalidate>
    @csrf
    @if ($yo)
        <h2>Comprar con tu cuenta</h2>
        <p class="mu">Estás registrado como <b style="color:var(--tx)">{{ $yo->nombre }}</b> ({{ $yo->email }}).</p>
        @if ($evento->pedir_colegio && !$yo->colegio)
        <label class="lb" for="colegio">Colegio</label>
        <input class="in" id="colegio" name="colegio" value="{{ old('colegio') }}" maxlength="120" required>
        @endif
        <button class="btn btn-c" style="margin-top:20px"><i class="fa-solid fa-check"></i> Terminar registro</button>
    @else
        <h2>Tus datos</h2>
        <p class="mu" style="font-size:.9rem">Tu entrada es personal: en la puerta revisan tu documento.</p>

        <label class="lb" for="nombre">Nombre completo</label>
        <input class="in" id="nombre" name="nombre" value="{{ old('nombre') }}" autocomplete="name" maxlength="120" required>

        <label class="lb" for="email">Correo</label>
        <input class="in" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" maxlength="150" required>

        <label class="lb">Documento</label>
        <div class="dos">
            <select class="in" name="tipo_documento" aria-label="Tipo de documento">
                @foreach (\App\Models\EventoAsistente::DOCUMENTOS as $k => $n)
                <option value="{{ $k }}" @selected(old('tipo_documento', 'CC') === $k)>{{ $k === 'CC' ? 'Cédula' : ($k === 'TI' ? 'Tarjeta de identidad' : $n) }}</option>
                @endforeach
            </select>
            <input class="in" name="documento" value="{{ old('documento') }}" inputmode="numeric" placeholder="Número" maxlength="20" required aria-label="Número de documento">
        </div>

        <label class="lb" for="fnac">Fecha de nacimiento</label>
        <input class="in" id="fnac" type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" max="{{ now()->subYear()->format('Y-m-d') }}" required>

        @if ($evento->pedir_colegio)
        <label class="lb" for="colegio">Colegio</label>
        <input class="in" id="colegio" name="colegio" value="{{ old('colegio') }}" maxlength="120" required>
        @endif

        <label class="lb" for="cel">Celular (WhatsApp)</label>
        <input class="in" id="cel" type="tel" name="celular" value="{{ old('celular') }}" inputmode="tel" autocomplete="tel" maxlength="20" required>

        <label class="lb" for="clave">Crea una clave</label>
        <div class="ojo"><input class="in" id="clave" type="password" name="password" autocomplete="new-password" minlength="6" required><button type="button" data-ojo="clave" aria-label="Ver clave"><i class="fa-regular fa-eye"></i></button></div>
        <small class="mu">Mínimo 6 caracteres. Con tu correo y esta clave entras a ver tu entrada.</small>

        <label class="lb" for="clave2">Repite la clave</label>
        <div class="ojo"><input class="in" id="clave2" type="password" name="password_confirmation" autocomplete="new-password" minlength="6" required><button type="button" data-ojo="clave2" aria-label="Ver clave"><i class="fa-regular fa-eye"></i></button></div>

        <label class="chk" id="acudiente" style="display:none"><input type="checkbox" name="autoriza_acudiente" value="1" @checked(old('autoriza_acudiente'))>
            <span>Soy menor de edad y mi <b>padre, madre o acudiente</b> conoce y autoriza este registro y la asistencia al evento.</span></label>

        <label class="chk"><input type="checkbox" name="acepta_datos" value="1" @checked(old('acepta_datos')) required>
            <span>Autorizo a <b>{{ $tienda->nombre }}</b> a usar mis datos para gestionar mi entrada a este evento (Ley 1581 de 2012) y acepto las condiciones del evento.</span></label>

        <button class="btn btn-c" style="margin-top:10px" id="ev-enviar"><i class="fa-solid fa-check"></i> Terminar registro</button>
        <p class="mu" style="text-align:center;margin-top:16px;font-size:.92rem">¿Ya tienes cuenta? <a href="{{ route('mis-entradas.ingresar', ['e' => $evento->slug]) }}" style="color:var(--c);font-weight:700">Ingresa aquí</a></p>
    @endif
</form>
@endsection

@section('js')
<script>
(function () {
    document.querySelectorAll('[data-ojo]').forEach(function (b) {
        b.addEventListener('click', function () { var i = document.getElementById(b.dataset.ojo); i.type = i.type === 'password' ? 'text' : 'password'; });
    });
    var f = document.getElementById('fnac'), ac = document.getElementById('acudiente');
    function edad() {
        if (!f || !f.value) { if (ac) ac.style.display = 'none'; return; }
        var n = new Date(f.value + 'T00:00:00'), h = new Date(), e = h.getFullYear() - n.getFullYear();
        if (h.getMonth() < n.getMonth() || (h.getMonth() === n.getMonth() && h.getDate() < n.getDate())) e--;
        ac.style.display = e < 18 ? '' : 'none';
    }
    if (f) { f.addEventListener('change', edad); edad(); }
    var form = document.getElementById('ev-form'), btn = form.querySelector('button.btn-c');
    form.addEventListener('submit', function () { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…'; });
})();
</script>
@endsection
