<article class="card {{ $p['disponible'] ? '' : 'off' }}" data-q="{{ mb_strtolower($p['nombre'] . ' ' . $p['codigo'] . ' ' . implode(' ', $p['categorias'])) }}" data-id="{{ $p['id'] }}">
    <a href="{{ $p['url'] }}" class="ph">
        @if ($p['img'])<img src="{{ $p['img'] }}" alt="{{ $p['nombre'] }}" loading="lazy">@else{{ mb_strtoupper(mb_substr($p['nombre'], 0, 1)) }}@endif
        @if (!$p['disponible'])<span class="tag agot">Agotado</span>@elseif (count($p['variantes']) > 1)<span class="tag">{{ count($p['variantes']) }} opciones</span>@endif
        @if ($p['descuento'] && $p['disponible'])<span class="tag-promo">-{{ $p['descuento'] }}%</span>@endif
    </a>
    <div class="b">
        <a href="{{ $p['url'] }}"><h3>{{ $p['nombre'] }}</h3></a>
        <div class="pr">@if ($p['antes'])<span class="antes">@pesos($p['antes'])</span>@endif<span class="{{ $p['antes'] ? 'ahora-promo' : '' }}">@if ($p['desde'])<small>Desde</small>@pesos($p['desde'])@else @pesos($p['precio'])@endif</span> @if ($p['unidad'])<small>/ {{ $p['unidad'] }}</small>@endif</div>
        @if ($p['disponible'])
        <div class="bt">
            @if (count($p['variantes']) > 1)
            <a class="btn-c" href="{{ $p['url'] }}">Comprar</a>
            @else
            <button class="btn-c" data-comprar="{{ $p['id'] }}">💬 Comprar</button>
            <button class="btn-car" data-agregar="{{ $p['id'] }}" aria-label="Agregar al pedido">+</button>
            @endif
        </div>
        @endif
    </div>
</article>
