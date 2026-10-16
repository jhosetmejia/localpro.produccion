<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <meta name="description" content="Cree su cuenta en LocalPRO" />
    <title>Crear cuenta — LocalPRO</title>
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <div>
            <h2 class="serif">Ordene su negocio <em>desde hoy.</em></h2>
            <p>Cree su cuenta, configure su tienda en 4 pasos y empiece a vender hoy mismo.</p>
            <ul class="auth-list">
                <li><span><i class="fa-solid fa-check"></i></span> Registro en menos de un minuto</li>
                <li><span><i class="fa-solid fa-check"></i></span> Sin instalar programas</li>
                <li><span><i class="fa-solid fa-check"></i></span> Funciona en cualquier país y moneda</li>
            </ul>
        </div>
        <small>© {{ date('Y') }} LocalProo · Todos los derechos reservados</small>
    </aside>

    <main class="auth-main">
        <div class="auth-card rise">
            <a href="{{ url('/') }}" class="back"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
            @if (($tipoRegistro ?? null) === 'contable')
            <h1 class="serif">Crear cuenta de contador</h1>
            <p class="sub">Paso 1 · Sus datos. Al terminar entra directo a <b>Contabilidad</b>: cruce de facturas DIAN, IVA, retenciones y borrador del 350.</p>
            @else
            <h1 class="serif">Crear cuenta</h1>
            <p class="sub">Paso 1 de 4 · Sus datos como dueño del negocio. En 3 minutos estará vendiendo.</p>
            @endif

            @if ($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('registro') }}" method="post" novalidate>
                @csrf
                <div class="field">
                    <label for="name">Nombre completo</label>
                    <div class="input">
                        <i class="fa-regular fa-user"></i>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Su nombre" autocomplete="name" required />
                    </div>
                </div>
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <div class="input">
                        <i class="fa-regular fa-envelope"></i>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="usted@negocio.com" autocomplete="email" required />
                    </div>
                </div>
                <div class="field">
                    <label for="pais">País</label>
                    <div class="input">
                        <i class="fa-solid fa-earth-americas"></i>
                        <select id="pais" name="pais" required data-buscar style="width:100%;padding:.95rem 1rem .95rem 2.8rem;border-radius:16px;border:2px solid var(--line);background:#fafdfb;font:inherit;font-weight:600;color:var(--ink);appearance:auto">
                            @foreach ($paises as $codigo => $p)
                            <option value="{{ $codigo }}" data-ind="{{ $p[6] }}" data-mon="{{ $p[1] }}" @selected(old('pais', 'CO') === $codigo)>{{ $p[0] }} · {{ $p[1] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <small style="color:var(--muted);font-size:.8rem">Los precios y las facturas saldrán en la moneda de su país.</small>
                </div>
                <div class="field">
                    <label for="telefono">Celular / WhatsApp</label>
                    <div class="input">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span id="ind" style="position:absolute;left:2.6rem;top:50%;transform:translateY(-50%);font-weight:700;color:var(--muted);z-index:1;line-height:1">+57</span>
                        <input id="telefono" name="telefono" type="tel" value="{{ old('telefono') }}" placeholder="300 123 4567" autocomplete="tel-national" data-sin-indicativo required style="padding-left:5rem" />
                    </div>
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input id="password" name="password" type="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required />
                        <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" onclick="var p=document.getElementById('password');p.type=p.type==='password'?'text':'password'"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repita la contraseña" autocomplete="new-password" required />
                    </div>
                </div>
                <div class="field">
                    <label for="codigo_asesor">Código de asesor <span style="font-weight:500;opacity:.7">(opcional)</span></label>
                    <div class="input">
                        <i class="fa-solid fa-user-tie"></i>
                        <input id="codigo_asesor" name="codigo_asesor" type="text" maxlength="20" placeholder="Si un asesor le ayudó, su código (ej. LCP-00012)" value="{{ old('codigo_asesor', $codigoAsesor ?? '') }}" style="text-transform:uppercase" autocomplete="off" />
                    </div>
                </div>
                <label class="terminos" style="display:flex;gap:.6rem;align-items:flex-start;margin:.4rem 0 .2rem;font-size:.9rem;line-height:1.45;cursor:pointer">
                    <input type="checkbox" name="acepto" value="1" {{ old('acepto') ? 'checked' : '' }} required style="width:20px;height:20px;flex:none;margin-top:2px;accent-color:#16b34a" />
                    <span>Acepto las <a href="{{ route('legal.terminos') }}" target="_blank" rel="noopener" style="font-weight:800">condiciones de uso</a> y la <a href="{{ route('legal.privacidad') }}" target="_blank" rel="noopener" style="font-weight:800">política de privacidad</a>. Entiendo que si al terminar la prueba gratis no paso a un plan, se eliminan los productos, imágenes y demás información del negocio, y se conservan solo los datos de contacto.</span>
                </label>
                <button class="btn btn-amber btn-block" type="submit" style="margin-top:.6rem">Continuar · Datos del negocio <i class="fa-solid fa-arrow-right"></i></button>
            </form>

            <script>
                (function () {
                    var s = document.getElementById('pais'), i = document.getElementById('ind');
                    var f = function () { i.textContent = '+' + s.options[s.selectedIndex].dataset.ind; };
                    s.addEventListener('change', f); f();
                })();
            </script>
            <p class="alt">¿Ya tiene cuenta? <a href="{{ route('login') }}">Ingresar</a></p>
        </div>
    </main>
</div>
@include('layouts.partials.lp-ui')
</body>
</html>
