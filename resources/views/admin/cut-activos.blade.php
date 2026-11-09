{{-- Quién tiene LocalProCut abierto ahora mismo (se refresca solo cada 15 s) --}}
@forelse ($activos as $v)
<tr>
    <td><span class="cut-dot"></span><b>{{ $v->user?->name ?? 'Visitante ' . substr($v->visitante, 0, 5) }}</b>
        <div class="cut-sub">{{ $v->nuevo ? 'Primera vez' : 'Ya había venido' }}</div></td>
    <td>@if ($v->geo_consultada){{ $v->ciudad ?: '?' }}, {{ $v->region }}<div class="cut-sub">{{ $v->pais }} · {{ $v->operador }}@if ($v->datos_moviles) · 📶 datos móviles @endif @if ($v->vpn) · <b class="text-danger">VPN/servidor</b>@endif</div>@else<span class="cut-sub">Ubicando…</span>@endif</td>
    <td>{{ $v->dispositivo === 'Celular' ? '📱' : ($v->dispositivo === 'Tablet' ? '📲' : '💻') }} {{ $v->marca ?: $v->dispositivo }}<div class="cut-sub">{{ $v->sistema }} · {{ $v->navegador }}@if ($v->es_app) · <b style="color:#16b84e">App instalada</b>@endif</div></td>
    <td>{{ $v->origen ?: ($v->app_origen ?: ($v->referencia ?: 'Directo')) }}@if ($v->invito)<div class="cut-sub">🤝 invitado por un amigo</div>@endif</td>
    <td><b>{{ \App\Http\Controllers\adminCutController::nombreEvento($v->ultimo_evento) }}</b><div class="cut-sub">{{ $v->videos }} video(s) · {{ $v->exportados }} exportado(s)</div></td>
    <td class="text-nowrap">{{ intdiv($v->segundos, 60) }} min<div class="cut-sub">{{ $v->ultima_vez?->diffForHumans() }}</div></td>
</tr>
@empty
<tr><td colspan="6" class="text-center text-muted fw-semibold py-3">Nadie tiene el editor abierto en este momento.</td></tr>
@endforelse
