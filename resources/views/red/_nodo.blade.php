{{-- Una persona de la red, sus negocios y (adentro) su equipo. Uso: @include('red._nodo', ['n' => $asesor, 'hijos' => .., 'negocios' => .., 'admin' => bool]) --}}
@php
    $equipo = $hijos[$n->id] ?? collect();
    $suyos = $negocios[$n->id] ?? collect();
@endphp
<li>
    <details @if (($nivel ?? 0) < 2) open @endif>
        <summary class="rn {{ $n->cargo }} {{ $n->activo ? '' : 'off' }}">
            <span class="rn-av"><i class="fa-solid {{ $n->cargo_icono }}"></i></span>
            <span class="rn-tx">
                <b>{{ $n->nombre }}</b>
                <small>{{ $n->cargo_nombre }}{{ $n->ciudad ? ' · ' . $n->ciudad : ($n->departamento ? ' · ' . $n->departamento : '') }}{{ $n->activo ? '' : ' · inactivo' }}</small>
            </span>
            <span class="rn-ct" title="Negocios propios / personas en su equipo"><i class="fa-solid fa-store"></i> {{ $suyos->count() }}@if ($equipo->count()) <i class="fa-solid fa-users ms-2"></i> {{ $equipo->count() }}@endif</span>
            @if (!empty($admin))<a href="{{ route('admin.red.show', $n) }}" class="rn-ed" title="Ver / editar"><i class="fa-solid fa-pen"></i></a>@endif
        </summary>
        @if ($suyos->isNotEmpty() || $equipo->isNotEmpty())
        <ul>
            @foreach ($suyos as $t)
            @php
                $estado = !$t->plan_vence ? 'gratis' : ($t->plan_vence->isPast() ? 'vencido' : (now()->diffInDays($t->plan_vence) <= 30 ? 'pronto' : 'ok'));
            @endphp
            <li class="rn-neg {{ $estado }}">
                <span class="dot"></span>
                @if (!empty($admin))<a href="{{ route('admin.tiendas.show', $t) }}">{{ $t->nombre }}</a>@else<span>{{ $t->nombre }}</span>@endif
                <small>{{ $t->plan->nombre ?? 'Sin plan' }}{{ $t->plan_vence ? ' · ' . ($t->plan_vence->isPast() ? 'venció ' : 'vence ') . $t->plan_vence->format('d/m/Y') : '' }}</small>
            </li>
            @endforeach
            @foreach ($equipo as $h)
                @include('red._nodo', ['n' => $h, 'nivel' => ($nivel ?? 0) + 1])
            @endforeach
        </ul>
        @endif
    </details>
</li>
