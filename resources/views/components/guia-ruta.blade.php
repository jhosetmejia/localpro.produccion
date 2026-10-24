@props(['ruta' => [], 'tipo' => null])
@php
    $ruta = array_values((array) $ruta);
    $destino = $ruta[0] ?? 'panel';
    $menu = \App\Support\GuiaVisual::menu($tipo, $destino);
    $primero = \App\Support\GuiaVisual::item($destino);
    $pasos = array_merge([['Menú', $primero['nombre'], $primero['icono']]], array_map(fn ($p) => [null, $p, null], array_slice($ruta, 1)));
    $uid = 'gv' . substr(md5(json_encode($ruta) . $tipo), 0, 8);
@endphp
@once
<style>
.gv{display:grid;grid-template-columns:190px 1fr;border:1.5px solid #dfe7e2;border-radius:16px;overflow:hidden;background:#fff;margin:.8rem 0 1rem;font-family:inherit;box-shadow:0 10px 30px -22px rgba(0,0,0,.4)}
.gv-side{background:#0b0d0c;color:#cfd8d2;padding:.6rem .4rem;display:flex;flex-direction:column;gap:2px}
.gv-side .it{display:flex;align-items:center;gap:.5rem;padding:.38rem .55rem;border-radius:9px;font-size:.78rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;opacity:.75}
.gv-side .it i{width:16px;text-align:center;font-size:.8rem}
.gv-side .it.on{background:#a6f34a;color:#0b0d0c;font-weight:800;opacity:1;position:relative;animation:gvLatido 2.2s ease-in-out infinite}
.gv-side .it.on::after{content:"👆";position:absolute;right:6px;font-size:1rem;animation:gvDedo 1.4s ease-in-out infinite}
.gv-main{padding:.7rem .9rem;background:#f7faf8;min-width:0}
.gv-bar{display:flex;align-items:center;gap:.35rem;margin-bottom:.7rem}
.gv-bar span{width:9px;height:9px;border-radius:50%;background:#e5e7eb}
.gv-bar b{margin-left:.4rem;font-size:.72rem;color:#6b7570;font-weight:600;background:#fff;border:1px solid #e5e7eb;border-radius:999px;padding:.12rem .6rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gv-pasos{display:flex;flex-wrap:wrap;align-items:center;gap:.4rem}
.gv-paso{display:inline-flex;align-items:center;gap:.4rem;background:#fff;border:1.5px solid #dfe7e2;border-radius:12px;padding:.4rem .65rem;font-size:.85rem;font-weight:700;color:#0b0d0c;opacity:0;transform:translateY(6px);animation:gvEntra .45s ease forwards}
.gv-paso .n{display:grid;place-items:center;width:20px;height:20px;border-radius:50%;background:#0b0d0c;color:#a6f34a;font-size:.7rem;font-weight:900;flex:0 0 20px}
.gv-paso small{color:#6b7570;font-weight:600}
.gv-paso.fin{border-color:#16b34a;background:#ecfdf3;box-shadow:0 0 0 3px rgba(22,179,74,.15)}
.gv-flecha{color:#9aa5a0;font-size:.8rem;opacity:0;animation:gvEntra .3s ease forwards}
@keyframes gvEntra{to{opacity:1;transform:none}}
@keyframes gvLatido{0%,100%{box-shadow:0 0 0 0 rgba(166,243,74,.6)}50%{box-shadow:0 0 0 6px rgba(166,243,74,0)}}
@keyframes gvDedo{0%,100%{transform:translateX(0)}50%{transform:translateX(-4px)}}
@media (max-width:620px){.gv{grid-template-columns:1fr}.gv-side{flex-direction:row;overflow-x:auto;padding:.5rem}.gv-side .it{flex:0 0 auto}.gv-side .it.on::after{display:none}}
@media (prefers-reduced-motion:reduce){.gv *{animation:none!important;opacity:1!important;transform:none!important}}
</style>
@endonce
<div class="gv" id="{{ $uid }}" role="img" aria-label="Dónde hacerlo: {{ collect($pasos)->pluck(1)->join(' → ') }}">
    <div class="gv-side" aria-hidden="true">
        @foreach ($menu as $m)<div class="it {{ $m['activo'] ? 'on' : '' }}"><i class="fa-solid {{ $m['icono'] }}"></i>{{ $m['nombre'] }}</div>@endforeach
    </div>
    <div class="gv-main">
        <div class="gv-bar" aria-hidden="true"><span></span><span></span><span></span><b>localproo.com · LocalPro</b></div>
        <div class="gv-pasos">
            @foreach ($pasos as $i => [$pre, $txt, $ic])
            @if ($i > 0)<i class="fa-solid fa-chevron-right gv-flecha" style="animation-delay:{{ $i * .5 - .2 }}s"></i>@endif
            <span class="gv-paso {{ $loop->last ? 'fin' : '' }}" style="animation-delay:{{ $i * .5 }}s"><span class="n">{{ $i + 1 }}</span>@if ($ic)<i class="fa-solid {{ $ic }}"></i>@endif @if ($pre)<small>{{ $pre }}:</small>@endif {{ $txt }}</span>
            @endforeach
        </div>
    </div>
</div>
