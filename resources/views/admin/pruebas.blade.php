@extends('layouts.app')

@section('title', 'Probar sistemas')

@section('content')
<style>
.pz-sel{max-width:420px}
.pz-ciclo{display:flex;flex-direction:column;gap:0;counter-reset:pz}
.pz-paso{display:flex;gap:.75rem;align-items:flex-start;position:relative;padding-bottom:1rem}
.pz-paso:not(:last-child)::before{content:"";position:absolute;left:19px;top:40px;bottom:0;width:2px;background:var(--lp-line)}
.pz-ic{flex:none;width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:#eefbf1;color:#16b34a;font-size:1rem}
.pz-paso b{display:block;font-size:.85rem}
.pz-paso span{font-size:.88rem;color:#475569}
.pz-roles td,.pz-roles th{font-size:.86rem;vertical-align:top}
.pz-perm{display:inline-block;background:#f1f5f2;border-radius:8px;padding:.1rem .45rem;margin:.1rem .15rem .1rem 0;font-size:.76rem}
.pz-res li{display:flex;gap:.6rem;align-items:flex-start;padding:.45rem 0;border-bottom:1px dashed var(--lp-line);font-size:.88rem}
.pz-res li:last-child{border-bottom:0}
.pz-res .ok{color:#16a34a}.pz-res .no{color:#dc2626}
.pz-res small{color:#64748b}
.pz-tag{font-size:.75rem;font-weight:800;border-radius:999px;padding:.2rem .6rem}
.pz-tag.ok{background:#dcfce7;color:#11702f}.pz-tag.no{background:#fee2e2;color:#b91c1c}
@media (max-width:900px){.pz-dos{grid-template-columns:1fr!important}}
</style>
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Probar <span>sistemas</span></h1>
            <span class="text-muted fw-semibold">Elija el sistema, revise su ciclo de vida y pruébelo de punta a punta.</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-box mb-3">
        <label class="form-label fw-bold" for="pzSistema">¿Qué sistema quiere probar?</label>
        <select id="pzSistema" class="form-select pz-sel" onchange="if(this.value) location='{{ route('admin.pruebas') }}?sistema='+this.value">
            <option value="">Seleccione el sistema…</option>
            @foreach ($sistemas as $k => [$nombre, $icono, $listo])
                <option value="{{ $k }}" @selected($sel === $k) @disabled(!$listo)>{{ $nombre }}{{ $listo ? '' : ' (próximamente)' }}</option>
            @endforeach
        </select>
    </div>

    @if ($sel === 'restaurante')
        <div class="lp-grid pz-dos" style="grid-template-columns:1fr 1fr">
            @foreach ($ciclos as $modo => [$titulo, $pasos])
                <div class="lp-box">
                    <h3><span><i class="fa-solid fa-route me-1"></i> Ciclo de vida: {{ $titulo }}</span></h3>
                    <div class="pz-ciclo">
                        @foreach ($pasos as $i => [$quien, $ic, $txt])
                            <div class="pz-paso"><div class="pz-ic"><i class="fa-solid {{ $ic }}"></i></div><div><b>{{ $i + 1 }}. {{ $quien }}</b><span>{{ $txt }}</span></div></div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="lp-box mb-3">
            <h3><span><i class="fa-solid fa-user-gear me-1"></i> Roles que quedan creados al abrir un restaurante</span></h3>
            <p class="text-muted small mb-2">Se crean solos con sus permisos cuando el negocio es restaurante o cafetería (o cuando encienden Pedidos). El dueño solo asigna cada persona a su rol.</p>
            <div class="table-responsive">
                <table class="table pz-roles mb-0">
                    <thead><tr><th>Rol</th><th>Puede</th><th>Al entrar ve</th><th>Estación</th></tr></thead>
                    <tbody>
                        @foreach ($roles as $r)
                            <tr>
                                <td class="fw-bold text-nowrap"><i class="fa-solid {{ $r['icono'] }} me-1 text-success"></i>{{ $r['nombre'] }}</td>
                                <td>@if ($r['permisos'] === ['*'])<span class="pz-perm">Todo el negocio</span>@else @foreach ($r['permisos'] as $p)<span class="pz-perm" title="{{ $textos[$p] ?? $p }}">{{ \Illuminate\Support\Str::before($textos[$p] ?? $p, ':') }}</span>@endforeach @endif</td>
                                <td>{{ $r['inicio'] }}</td>
                                <td>{{ $r['estacion'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3><span><i class="fa-solid fa-robot me-1"></i> Prueba automática</span></h3>
            <p class="text-muted small">Crea un restaurante nuevo como lo haría un cliente, revisa los roles, crea mesero, caja, cocina y bar, y hace todo el recorrido con las mismas pantallas que ellos usan. Al terminar <b>se deshace todo</b>: no queda nada guardado.</p>
            <form method="post" action="{{ route('admin.pruebas.ejecutar') }}" class="d-flex flex-wrap gap-2" onsubmit="this.querySelectorAll('button').forEach(b=>b.disabled=true);this.querySelector('[data-c]').classList.remove('d-none')">
                @csrf
                <button name="modo" value="ambos" class="btn btn-primary"><i class="fa-solid fa-play me-1"></i> Probar los dos ciclos</button>
                <button name="modo" value="antes" class="btn btn-outline-dark">Solo "paga primero"</button>
                <button name="modo" value="despues" class="btn btn-outline-dark">Solo "paga al final"</button>
                <span data-c class="d-none align-self-center text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i> Probando…</span>
            </form>

            @if ($resultados)
                <div class="lp-grid pz-dos mt-3 mb-0" style="grid-template-columns:repeat({{ count($resultados) }},1fr)">
                    @foreach ($resultados as $r)
                        <div class="border rounded-4 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <b>{{ $r['titulo'] }}</b>
                                <span class="pz-tag {{ $r['ok'] ? 'ok' : 'no' }}">{{ $r['ok'] ? 'TODO BIEN' : 'FALLÓ' }} · {{ $r['segundos'] }} s</span>
                            </div>
                            <ul class="list-unstyled pz-res mb-0">
                                @foreach ($r['pasos'] as $p)
                                    <li>
                                        <i class="fa-solid {{ $p['ok'] ? 'fa-circle-check ok' : 'fa-circle-xmark no' }} mt-1"></i>
                                        <div><div class="fw-semibold">{{ $p['n'] }}</div><small>{{ $p['d'] }}</small></div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lp-box mb-3">
            <h3><span><i class="fa-solid fa-mobile-screen-button me-1"></i> Recorrido a mano (con personas reales)</span></h3>
            <p class="text-muted small mb-2">Deja guardado un <b>Restaurante de prueba (QA)</b> con carta, caja abierta y un usuario por rol. Abra cada usuario en un celular distinto o en una ventana de incógnito y haga el pedido de verdad.</p>
            @if ($qa)
                <div class="table-responsive mb-2">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Rol</th><th>Usuario</th></tr></thead>
                        <tbody>@foreach ($qaUsuarios as $u)<tr><td>{{ $u['rol'] }}</td><td><code>{{ $u['email'] }}</code></td></tr>@endforeach</tbody>
                    </table>
                </div>
                <ol class="small mb-3">
                    <li><b>Mesero</b>: Pedidos → Tomar pedido (cliente o mesa) → enviar.</li>
                    <li><b>Caja</b>: Pedidos por cobrar → Cobrar → confirmar en el POS.</li>
                    <li><b>Cocina</b> y <b>Bar</b>: cada uno ve lo suyo → "Todo listo".</li>
                    <li><b>Mesero</b>: suena el aviso → "Marcar entregado".</li>
                </ol>
            @endif
            <form method="post" action="{{ route('admin.pruebas.recorrido') }}" onsubmit="return {{ $qa ? "confirm('Se pone una clave nueva a todos los usuarios de prueba. ¿Seguir?')" : 'true' }}">
                @csrf
                <button class="btn btn-dark"><i class="fa-solid fa-utensils me-1"></i> {{ $qa ? 'Nueva clave para los usuarios de prueba' : 'Crear restaurante de prueba' }}</button>
            </form>
        </div>
    @endif
</div>
@endsection
