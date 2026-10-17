@extends('layouts.app')

@section('title', 'Pasaste la prueba básica')

@push('css')
<style>
.lpx .nota{display:flex;align-items:center;gap:14px;margin-top:16px;position:relative;z-index:1}
.lpx .nota b{font-size:2.4rem;line-height:1;background:var(--g);-webkit-background-clip:text;background-clip:text;color:transparent}
.lpx .nota span{color:#cfd8d2;font-weight:600}
.lpx .enlace{display:flex;gap:8px;align-items:center;background:#f4f8f5;border:1.5px dashed #bfe8c8;border-radius:16px;padding:10px 10px 10px 14px;margin:12px 0}
.lpx .enlace code{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:800;font-size:.95rem;color:var(--ink);font-family:inherit}
.lpx .enlace button{flex:none;padding:.55rem 1rem;font-size:.9rem}
.lpx .dos{display:grid;grid-template-columns:1fr 1fr;gap:10px}
@media (max-width:520px){.lpx .dos{grid-template-columns:1fr}}
.lpx .perfil{display:flex;gap:12px;align-items:flex-start;background:#fff8e6;border:1.5px solid #f3d27a;border-radius:20px;padding:16px;margin-bottom:14px;text-decoration:none;color:inherit}
.lpx .perfil .e{font-size:1.6rem;line-height:1}
.lpx .perfil small{display:block;color:var(--mut);font-weight:600}

    
.lpx{font-family:'Plus Jakarta Sans',system-ui,sans-serif;-webkit-font-smoothing:antialiased;margin:-.5rem -.25rem 0}
.lpx .card{display:block;flex-direction:initial;min-width:0;word-wrap:normal}
.lpx .btn:hover,.lpx .btn:focus,.lpx .btn:active{filter:brightness(.96);box-shadow:none}
.lpx .w{padding-top:0}

</style>
@endpush

@section('content')
@include('curso._estilos_panel')
<div class="lpx">
<div class="w">
    @include('curso._top_panel')

    <div class="hero">
        <small>Prueba básica aprobada</small>
        <h1>¡Pasaste la prueba básica, <em>{{ strtok($a->nombre, ' ') }}</em>! 🎉</h1>
        <p>Ya conoces qué es LocalPro, cómo se inscribe un negocio y cómo ganas. Ahora compártele el resultado a quien te reclutó.</p>
        @if ($a->curso_nota)
        <div class="nota"><b>{{ $a->curso_nota }}%</b><span>de respuestas correctas</span></div>
        @endif
    </div>

    <div class="card">
        <h2 style="margin:0 0 4px;font-size:1.25rem">Comparte este enlace con tu reclutador</h2>
        <p style="margin:0;color:var(--mut);font-weight:600">
            {{ $reclutador ? 'Envíaselo a ' . $reclutador->nombre . ' para que vea que ya pasaste la prueba.' : 'Envíaselo a la persona de LocalPro que te contactó para que vea que ya pasaste la prueba.' }}
        </p>

        @if ($enlace)
        <div class="enlace">
            <code id="enlace">{{ $enlace }}</code>
            <button type="button" class="btn btn-k" id="copiar"><i class="fa-regular fa-copy"></i> Copiar</button>
        </div>
        @else
        <div class="msg ok" style="margin:12px 0">Vista de administrador: aquí el asesor ve su propio enlace (localproo.com/prueba/SU-CÓDIGO).</div>
        @endif

        <a class="btn btn-w btn-block" target="_blank" rel="noopener" href="{{ $wa }}"><i class="fa-brands fa-whatsapp"></i> Enviar a mi reclutador por WhatsApp</a>
    </div>

    @if (!$admin && $a->falta_perfil)
    <a class="perfil" href="{{ route('cuenta.edit') }}#pago">
        <div class="e">📝</div>
        <div><b>Siguiente paso: completa tu información en el perfil</b>
            <small>Tu cédula y dónde te pagamos las comisiones. Lo puedes hacer cuando quieras desde tu panel.</small></div>
    </a>
    @endif

    <a href="{{ $panel }}" class="btn btn-g btn-block"><i class="fa-solid fa-gauge"></i> Ir a mi panel</a>

</div>
</div>
@endsection

@push('js')
<script>
(function () {
    var b = document.getElementById('copiar');
    if (!b) return;
    b.addEventListener('click', function () {
        var t = document.getElementById('enlace').textContent.trim();
        var listo = function () { b.innerHTML = '<i class="fa-solid fa-check"></i> Copiado'; setTimeout(function () { b.innerHTML = '<i class="fa-regular fa-copy"></i> Copiar'; }, 2000); };
        try { navigator.clipboard.writeText(t).then(listo, listo); } catch (e) { listo(); }
    });
})();
</script>
@endpush
