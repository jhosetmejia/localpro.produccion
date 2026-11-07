@php $av = \App\Support\SeparadosPorVencer::paraUsuario(auth()->user()); @endphp
@if ($av['mostrar'])
<li class="nav-item dropdown lp-campana">
    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notificaciones: {{ $av['total'] }} separados por vencer" title="Separados por vencer">
        <i class="fa-solid fa-bell {{ $av['total'] ? 'lp-campana-mueve' : '' }}"></i>
        @if ($av['total'])<span class="lp-campana-num">{{ $av['total'] > 99 ? '99+' : $av['total'] }}</span>@endif
    </a>
    <div class="dropdown-menu dropdown-menu-end lp-campana-menu">
        <div class="lp-campana-tit"><i class="fa-solid fa-bell me-1"></i> Separados por vencer</div>
        @if (!$av['total'])
            <div class="lp-campana-vacio"><i class="fa-regular fa-circle-check"></i> Todo al día: ningún separado vence en los próximos {{ \App\Support\SeparadosPorVencer::DIAS_ANTES }} días.</div>
        @endif
        @foreach ([['negocio', 'Mi negocio'], ['asesor', 'Mis negocios (asesor)']] as [$k, $lbl])
            @if ($av[$k]->isNotEmpty())
                @if ($av['negocio']->isNotEmpty() && $av['asesor']->isNotEmpty())<div class="lp-campana-sec">{{ $lbl }}</div>@endif
                @foreach ($av[$k] as $n)
                <div class="lp-campana-item {{ $n['nivel'] }}">
                    <a class="lp-campana-txt" href="{{ $n['url'] ?? ($n['wa'] ?? '#') }}" @if (!$n['url'] && $n['wa']) target="_blank" rel="noopener" @endif>
                        <b>{{ $n['cliente'] }}</b> · {{ $n['numero'] }}
                        @if ($n['tienda'])<small class="d-block">🏪 {{ $n['tienda'] }}</small>@endif
                        <small class="d-block"><span class="lp-campana-cuando">{{ $n['cuando'] }}</span> · le falta {{ $n['saldo'] }}</small>
                    </a>
                    @if ($n['wa'])<a class="lp-campana-wa" href="{{ $n['wa'] }}" target="_blank" rel="noopener" title="{{ $n['tienda'] ? 'Avisarle al negocio por WhatsApp' : 'Recordarle al cliente por WhatsApp' }}"><i class="fa-brands fa-whatsapp"></i></a>@endif
                </div>
                @endforeach
            @endif
        @endforeach
        @if ($av['negocio']->isNotEmpty() || \App\Support\SeparadosPorVencer::puedeVerNegocio(auth()->user()))
        <a class="lp-campana-pie" href="{{ route('separados.index', ['ver' => 'porvencer']) }}">Ver todos los separados por vencer</a>
        @endif
        <div class="lp-campana-nota">Al cliente con correo le llega un aviso {{ \App\Support\SeparadosPorVencer::DIAS_CORREO }} días antes, el día que vence y al día siguiente.</div>
    </div>
</li>
<style>
.lp-campana .nav-link{font-size:1.15rem;padding:.35rem .6rem}
.lp-campana-num{position:absolute;top:0;right:0;background:#dc2626;color:#fff;font-size:.65rem;font-weight:800;border-radius:999px;padding:1px 5px;line-height:1.3;border:2px solid #fff}
.lp-campana-mueve{animation:lpCampana 2.4s ease-in-out infinite;transform-origin:50% 0}
@keyframes lpCampana{0%,70%,100%{transform:rotate(0)}75%{transform:rotate(14deg)}80%{transform:rotate(-12deg)}85%{transform:rotate(8deg)}90%{transform:rotate(-6deg)}}
@media (prefers-reduced-motion:reduce){.lp-campana-mueve{animation:none}}
.lp-campana-menu{width:340px;max-width:92vw;max-height:70vh;overflow:auto;padding:0;border-radius:14px}
.lp-campana-tit{font-weight:800;padding:.7rem .9rem;border-bottom:1px solid #eee;position:sticky;top:0;background:#fff;z-index:1}
.lp-campana-sec{font-size:.72rem;font-weight:800;text-transform:uppercase;color:#6b7280;padding:.5rem .9rem .2rem}
.lp-campana-vacio{padding:1rem .9rem;color:#16a34a;font-size:.9rem}
.lp-campana-item{display:flex;align-items:center;gap:.4rem;padding:.55rem .9rem;border-left:4px solid #f59e0b;border-bottom:1px solid #f3f4f6}
.lp-campana-item.urgente{border-left-color:#ea580c}.lp-campana-item.vencido{border-left-color:#dc2626;background:#fef2f2}
.lp-campana-txt{flex:1;color:inherit;text-decoration:none;font-size:.88rem;min-width:0}
.lp-campana-txt small{color:#6b7280}.lp-campana-item.vencido .lp-campana-cuando{color:#dc2626;font-weight:700}.lp-campana-item.urgente .lp-campana-cuando{color:#ea580c;font-weight:700}
.lp-campana-wa{color:#16a34a;font-size:1.3rem;padding:.2rem .3rem}
.lp-campana-pie{display:block;text-align:center;padding:.6rem;font-weight:700;font-size:.88rem;text-decoration:none}
.lp-campana-nota{font-size:.72rem;color:#9ca3af;padding:0 .9rem .7rem;text-align:center}
</style>
@endif
