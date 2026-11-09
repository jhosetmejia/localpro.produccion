{{-- Quién está en LocalProo ahora mismo (se refresca solo cada 15 s) --}}
@forelse ($activos as $v)
<tr>
    <td><span class="cut-dot"></span><b>{{ $v->user?->name ?? 'Visitante ' . substr($v->visitante, 0, 5) }}</b>
        <div class="cut-sub">@if ($v->tienda)🏪 {{ $v->tienda->nombre }}@elseif ($v->user)Sin negocio @else{{ $v->nuevo ? 'Primera vez' : 'Ya había venido' }}@endif</div></td>
    <td>@if ($v->geo_consultada){{ $v->ciudad ?: '?' }}, {{ $v->region }}<div class="cut-sub">{{ $v->pais }} · {{ $v->operador }}@if ($v->datos_moviles) · 📶 datos @endif @if ($v->vpn) · <b class="text-danger">VPN</b>@endif</div>@else<span class="cut-sub">Ubicando…</span>@endif</td>
    <td>{{ $v->dispositivo === 'Celular' ? '📱' : ($v->dispositivo === 'Tablet' ? '📲' : '💻') }} {{ $v->marca ?: $v->dispositivo }}<div class="cut-sub">{{ $v->sistema }} · {{ $v->navegador }}@if ($v->es_app) · <b style="color:#16b84e">App</b>@endif</div></td>
    <td><b>{{ \App\Models\VisitaSitio::seccion($v->ultima_ruta, $v->ultima_pagina) }}</b><div class="cut-sub">{{ $v->ultima_pagina }}</div></td>
    <td>{{ $v->origen ?: ($v->app_origen ?: ($v->referencia ?: 'Directo')) }}</td>
    <td class="text-nowrap">{{ $v->paginas }} pág.<div class="cut-sub">{{ $v->created_at?->diffForHumans(null, true) }} en el sitio</div></td>
</tr>
@empty
<tr><td colspan="6" class="text-center text-muted fw-semibold py-3">No hay nadie en este momento.</td></tr>
@endforelse
