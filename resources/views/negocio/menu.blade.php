@extends('layouts.app')

@section('title', 'Personalizar menú')

@push('css')
<style>
.pm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:.6rem}
.pm-item{display:flex;align-items:center;gap:.7rem;background:#fff;border:1.5px solid #e3efe5;border-radius:16px;padding:.65rem .8rem;cursor:pointer;margin:0;transition:.15s}
.pm-item .ic{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:#f4fcf6;color:#11702f;flex:0 0 38px}
.pm-item .tx{flex:1;min-width:0;line-height:1.2}
.pm-item .tx b{display:block}
.pm-item .tx small{color:#6b7570}
.pm-item .form-check-input{width:2.6em;height:1.4em;margin:0;flex:0 0 auto;cursor:pointer}
.pm-item.off{opacity:.55;background:#f7f7f4}
.pm-item.off .ic{background:#eee;color:#999}
.pm-sug{font-size:.68rem;background:#fff5d6;color:#8a6400;border-radius:99px;padding:.1rem .45rem;font-weight:700}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Mi negocio</small><h1>Personalizar <span>menú</span></h1>
            <p class="text-muted mb-0">Apague lo que su negocio no usa y el menú queda más limpio. No se borra nada: lo puede volver a encender cuando quiera.</p></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('menu.update') }}" id="pm-form">
        @csrf @method('put')
        @foreach ($grupos as $grupo => $items)
        @php
            $items = collect($items)->filter(fn ($x, $k) => match ($k) {
                'obituarios' => (bool) $tienda->mod_obituarios,
                'consultorio' => (bool) $tienda->mod_consultorio,
                'contabilidad' => (bool) $tienda->mod_contable,
                'alquiler' => (bool) $tienda->mod_alquiler,
                'facturacion' => !$tienda->pais || $tienda->pais === 'CO',
                default => true,
            });
        @endphp
        @if ($items->isNotEmpty())
        <h3 class="h6 fw-bold text-uppercase text-muted mt-4 mb-2" style="letter-spacing:.06em">{{ $grupo }}</h3>
        <div class="pm-grid">
            @foreach ($items as $k => [$nom, $ic, $g, $desc])
            @php $on = $tienda->enMenu($k); @endphp
            <label class="pm-item {{ $on ? '' : 'off' }}">
                <span class="ic"><i class="fa-solid {{ $ic }}"></i></span>
                <span class="tx"><b>{{ $nom }} @if (in_array($k, $sugerido, true))<span class="pm-sug" title="Sugerido esconder para {{ mb_strtolower($tienda->tipo_nombre) }}">Poco usado</span>@endif</b><small>{{ $desc }}</small></span>
                <input type="hidden" name="ver[{{ $k }}]" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="ver[{{ $k }}]" value="1" @checked($on)>
            </label>
            @endforeach
        </div>
        @endif
        @endforeach

        <p class="small text-muted mt-3 mb-2"><i class="fa-solid fa-circle-info me-1"></i>Panel, Datos del negocio, Mi plan, Usuarios y esta pantalla siempre se ven. Esto cambia el menú para todos los usuarios del negocio.</p>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar menú</button>
            @if ($sugerido)
            <button class="btn btn-dark" name="accion" value="sugerido"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Usar lo sugerido para {{ mb_strtolower($tienda->tipo_nombre) }}</button>
            @endif
            <button class="btn btn-light" name="accion" value="todo"><i class="fa-solid fa-eye me-1"></i>Mostrar todo</button>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
document.querySelectorAll('.pm-item input[type=checkbox]').forEach(function (c) {
    c.addEventListener('change', function () { c.closest('.pm-item').classList.toggle('off', !c.checked); });
});
</script>
@endpush
