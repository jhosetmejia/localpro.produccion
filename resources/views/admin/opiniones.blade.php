@extends('layouts.app')

@section('title', 'Opiniones y fallas')

@push('css')
<style>
.lp-kpis{grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}
.op-filtros{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.8rem}
.op-filtros a{border:1px solid #dfe7e1;border-radius:999px;padding:.3rem .8rem;font-weight:700;font-size:.85rem;color:#151a17;text-decoration:none;background:#fff}
.op-filtros a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.op-item{border:1px solid #e3efe5;border-radius:16px;padding:.9rem 1rem;margin-bottom:.7rem;background:#fff}
.op-item.nuevo{border-left:5px solid #ff5c9a}
.op-item.revisando{border-left:5px solid #ffb02e}
.op-item.resuelto{border-left:5px solid #16b84e;opacity:.85}
.op-item.descartado{opacity:.6}
.op-msg{white-space:pre-wrap;font-weight:600;margin:.4rem 0;word-break:break-word}
.op-meta{color:#5b665e;font-size:.82rem}
.op-foto img{max-height:180px;border-radius:10px;border:1px solid #e3efe5;margin-top:.4rem}
.op-tec{font-size:.75rem;background:#f5f8f6;border-radius:10px;padding:.5rem .7rem;margin-top:.5rem;white-space:pre-wrap;word-break:break-word;max-height:220px;overflow:auto}
.op-err td{vertical-align:top;font-size:.85rem}
.op-err .msg{font-weight:700;word-break:break-word}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Opiniones <span>y fallas</span></h1>
            <span class="text-muted fw-semibold">Lo que la gente escribe con el botón <b>💬 Opinar</b> (panel y LocalProCut) y los errores que les salen en el navegador, que llegan solos.</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="lbl">Por revisar</div><div class="val">{{ $cuenta['abiertas'] }}</div><div class="sub">{{ $cuenta['nuevas'] }} sin abrir</div><div class="ic"><i class="fa-solid fa-comment-dots"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Fallas</div><div class="val">{{ $cuenta['fallas'] }}</div><div class="sub">abiertas</div><div class="ic"><i class="fa-solid fa-bug"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Ideas</div><div class="val">{{ $cuenta['ideas'] }}</div><div class="sub">abiertas</div><div class="ic"><i class="fa-solid fa-lightbulb"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Errores del navegador</div><div class="val">{{ $cuenta['errores'] }}</div><div class="sub">{{ $cuenta['errores_hoy'] }} pasaron hoy</div><div class="ic"><i class="fa-solid fa-triangle-exclamation"></i></div></div>
    </div>

    <div class="op-filtros mt-3">
        <a href="{{ route('admin.opiniones', array_filter(['modulo' => $modulo])) }}" class="{{ $vista === 'opiniones' ? 'on' : '' }}"><i class="fa-solid fa-comments me-1"></i>Lo que escribe la gente</a>
        <a href="{{ route('admin.opiniones', array_filter(['ver' => 'errores', 'modulo' => $modulo])) }}" class="{{ $vista === 'errores' ? 'on' : '' }}"><i class="fa-solid fa-triangle-exclamation me-1"></i>Errores automáticos ({{ $cuenta['errores'] }})</a>
    </div>

    @if ($modulos->count() > 1)
    <div class="op-filtros">
        <a href="{{ route('admin.opiniones', array_filter(['ver' => $vista === 'errores' ? 'errores' : null, 'estado' => $estado, 'tipo' => $tipo])) }}" class="{{ !$modulo ? 'on' : '' }}">Todos los módulos</a>
        @foreach ($modulos as $mo)
        <a href="{{ route('admin.opiniones', array_filter(['ver' => $vista === 'errores' ? 'errores' : null, 'estado' => $estado, 'tipo' => $tipo, 'modulo' => $mo])) }}" class="{{ $modulo === $mo ? 'on' : '' }}">{{ \App\Models\Opinion::MODULOS[$mo] ?? $mo }}</a>
        @endforeach
    </div>
    @endif

    @if ($vista === 'opiniones')
    <div class="op-filtros">
        @foreach (['abiertas' => 'Por revisar', 'nuevo' => 'Nuevas', 'revisando' => 'Revisando', 'resuelto' => 'Resueltas', 'descartado' => 'Descartadas', 'todas' => 'Todas'] as $k => $n)
        <a href="{{ route('admin.opiniones', array_filter(['estado' => $k, 'tipo' => $tipo, 'modulo' => $modulo])) }}" class="{{ $estado === $k ? 'on' : '' }}">{{ $n }}</a>
        @endforeach
        <span class="mx-1"></span>
        @foreach (\App\Models\Opinion::TIPOS as $k => $n)
        <a href="{{ route('admin.opiniones', array_filter(['estado' => $estado, 'tipo' => $tipo === $k ? null : $k, 'modulo' => $modulo])) }}" class="{{ $tipo === $k ? 'on' : '' }}">{{ \App\Models\Opinion::ICONOS[$k] }} {{ $n }}</a>
        @endforeach
    </div>

    @forelse ($opiniones as $o)
    <div class="op-item {{ $o->estado }}">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
                <b>{{ \App\Models\Opinion::ICONOS[$o->tipo] ?? '💬' }} {{ \App\Models\Opinion::TIPOS[$o->tipo] ?? $o->tipo }}</b>
                <span class="badge text-bg-light border ms-1">{{ \App\Models\Opinion::MODULOS[$o->modulo] ?? $o->modulo }}</span>
                <span class="badge {{ $o->estado === 'nuevo' ? 'text-bg-danger' : ($o->estado === 'resuelto' ? 'text-bg-success' : 'text-bg-secondary') }} ms-1">{{ \App\Models\Opinion::ESTADOS[$o->estado] ?? $o->estado }}</span>
            </div>
            <span class="op-meta">#{{ $o->id }} · {{ $o->created_at?->format('d/m/Y h:i a') }}</span>
        </div>
        <div class="op-msg">{{ $o->mensaje }}</div>
        <div class="op-meta">
            @if ($o->user)<i class="fa-solid fa-user me-1"></i>{{ $o->user->name }} ({{ $o->user->email }})@else<i class="fa-solid fa-user-secret me-1"></i>Sin sesión @endif
            @if ($o->tienda) · <i class="fa-solid fa-store me-1"></i>{{ $o->tienda->nombre }}@if ($o->tienda->telefono) · <a href="https://wa.me/{{ preg_replace('/\D/', '', $o->tienda->telefono) }}" target="_blank" rel="noopener">WhatsApp</a>@endif @endif
            @if ($o->contacto) · <i class="fa-solid fa-phone me-1"></i>{{ $o->contacto }}@endif
            @if ($o->pagina) · <i class="fa-solid fa-link me-1"></i>{{ $o->pagina }}@endif
        </div>
        @if ($o->foto)
        <a class="op-foto" href="{{ route('admin.opiniones.foto', $o) }}" target="_blank" rel="noopener"><img src="{{ route('admin.opiniones.foto', $o) }}" alt="Captura" loading="lazy"></a>
        @endif
        @if (!empty($o->datos))
        <details class="mt-2"><summary class="op-meta" style="cursor:pointer">Datos técnicos (navegador, pantalla, errores, editor)</summary>
            <div class="op-tec">{{ json_encode($o->datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
        </details>
        @endif
        <form method="post" action="{{ route('admin.opiniones.estado', $o) }}" class="mt-2">@csrf
            <textarea name="respuesta" class="form-control form-control-sm mb-2" rows="2" maxlength="2000" placeholder="Respuesta para la persona (la ve en 💬 Opinar → Mis reportes)">{{ $o->respuesta }}</textarea>
            <div class="d-flex gap-2 flex-wrap">
                @foreach (\App\Models\Opinion::ESTADOS as $k => $n)
                <button name="estado" value="{{ $k }}" class="btn btn-sm {{ $o->estado === $k ? 'btn-dark' : 'btn-outline-dark' }}">{{ $n }}</button>
                @endforeach
            </div>
        </form>
    </div>
    @empty
    <div class="lp-box text-center text-muted fw-semibold py-4">No hay reportes con este filtro. 🎉</div>
    @endforelse
    <div class="mt-2">{{ $opiniones->links() }}</div>

    @else
    <div class="lp-box">
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
            <h3 class="mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i>Errores que les salen a las personas</h3>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.opiniones', array_filter(['ver' => 'errores', 'modulo' => $modulo, 'resueltos' => request('resueltos') === '1' ? null : '1'])) }}">{{ request('resueltos') === '1' ? 'Ocultar resueltos' : 'Ver también resueltos' }}</a>
                <form method="post" action="{{ route('admin.opiniones.limpiar') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Borrar resueltos de hace +30 días</button></form>
            </div>
        </div>
        <p class="text-muted fw-semibold small mb-2">Llegan solos (sin que la persona escriba nada). El mismo error se agrupa: "veces" es cuántas veces pasó y "personas" cuántas personas distintas (aprox.). Si marca uno resuelto y vuelve a pasar, se reabre solo.</p>
        <div class="table-responsive">
        <table class="table table-sm op-err">
            <thead><tr><th>Error</th><th>Módulo</th><th class="text-end">Veces</th><th class="text-end">Personas</th><th>Última vez</th><th></th></tr></thead>
            <tbody>
            @forelse ($errores as $e)
            <tr class="{{ $e->resuelto ? 'text-muted' : '' }}">
                <td>
                    <div class="msg">{{ $e->mensaje }}</div>
                    <div class="op-meta">{{ $e->donde ?: 'sin archivo' }} · {{ $e->pagina }}</div>
                    <details><summary class="op-meta" style="cursor:pointer">Detalle</summary>
                        <div class="op-tec">{{ $e->navegador }}

{{ $e->pila }}

{{ $e->datos ? json_encode($e->datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</div>
                    </details>
                </td>
                <td>{{ \App\Models\Opinion::MODULOS[$e->modulo] ?? $e->modulo }}</td>
                <td class="text-end fw-bold">{{ number_format($e->veces, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($e->usuarios, 0, ',', '.') }}</td>
                <td class="text-nowrap">{{ $e->ultima_vez?->format('d/m h:i a') }}<div class="op-meta">desde {{ $e->primera_vez?->format('d/m/Y') }}</div></td>
                <td><form method="post" action="{{ route('admin.opiniones.error', $e) }}">@csrf<button class="btn btn-sm {{ $e->resuelto ? 'btn-outline-secondary' : 'btn-success' }}">{{ $e->resuelto ? 'Reabrir' : 'Resuelto' }}</button></form></td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted fw-semibold py-3">No hay errores del navegador. 🎉</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @endif
</div>
@endsection
