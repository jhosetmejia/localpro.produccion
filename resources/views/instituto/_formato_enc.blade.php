<div class="enc">
    <div class="lg">@if ($inst->logo_url)<img src="{{ $inst->logo_url }}" alt="">@else<span>{{ mb_strtoupper(mb_substr($inst->nombre, 0, 1)) }}</span>@endif</div>
    <div class="tt"><b>{{ mb_strtoupper($inst->nombre) }}</b><small>{{ $titulo }}</small></div>
    <div class="cd"><span>{{ $codigo }}</span><span>{{ $inst->nit ? 'NIT ' . $inst->nit : '' }}</span><span>{{ now()->format('d/m/Y') }}</span></div>
</div>
