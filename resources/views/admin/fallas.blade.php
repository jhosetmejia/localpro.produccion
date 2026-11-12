@extends('layouts.app')

@section('title', 'Fallas')

@push('css')
<style>
.lp-kpis{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}
.fa-filtros{display:flex;gap:.4rem;flex-wrap:wrap;margin:.2rem 0 .9rem}
.fa-filtros a{border:1px solid #dfe7e1;border-radius:999px;padding:.3rem .8rem;font-weight:700;font-size:.85rem;color:#151a17;text-decoration:none;background:#fff}
.fa-filtros a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.fa-card{border:1px solid #e3efe5;border-left:5px solid #e5243b;border-radius:16px;padding:1rem 1.1rem;margin-bottom:.8rem;background:#fff}
.fa-card.res{border-left-color:#16b84e;opacity:.75}
.fa-top{display:flex;gap:.8rem;align-items:flex-start}
.fa-ic{font-size:1.6rem;line-height:1;flex-shrink:0;width:44px;height:44px;border-radius:12px;background:#f5f8f6;display:grid;place-items:center}
.fa-int{font-weight:800;font-size:1.02rem;margin:0}
.fa-paso{font-weight:600;color:#3a443d;margin:.15rem 0 .35rem}
.fa-meta{display:flex;gap:.4rem;flex-wrap:wrap}
.fa-meta span{background:#f2f5f3;border-radius:999px;padding:.15rem .6rem;font-size:.78rem;font-weight:700;color:#3a443d}
.fa-meta .rojo{background:#ffe6ea;color:#b3123a}
.fa-det{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.3rem .9rem;margin:.6rem 0 .2rem;font-size:.85rem}
.fa-det b{color:#5b665e;font-weight:700;display:block;font-size:.75rem}
.fa-tec{font-size:.75rem;background:#f5f8f6;border-radius:10px;padding:.55rem .75rem;margin-top:.45rem;white-space:pre-wrap;word-break:break-word;max-height:260px;overflow:auto}
.fa-pie{display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap;margin-top:.55rem}
.fa-id{font-size:.75rem;color:#8a948d;font-weight:700}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Fallas <span>de la gente</span></h1>
            <span class="text-muted fw-semibold">Lo que la gente intentó hacer y no pudo. Llegan solas, aunque la persona no reporte nada. Si una falla resuelta vuelve a pasar, se reabre sola.</span></div>
    </div>

    <div class="lp-kpis">
        <div class="lp-kpi"><div class="lbl">Fallas abiertas</div><div class="val">{{ $cuenta['abiertas'] }}</div><div class="sub">distintas</div><div class="ic"><i class="fa-solid fa-bug"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Pasaron hoy</div><div class="val">{{ $cuenta['hoy'] }}</div><div class="sub">fallas distintas</div><div class="ic"><i class="fa-solid fa-calendar-day"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Personas afectadas</div><div class="val">{{ number_format($cuenta['personas'], 0, ',', '.') }}</div><div class="sub">aprox., en las abiertas</div><div class="ic"><i class="fa-solid fa-users"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Escritas por la gente</div><div class="val">{{ $cuenta['escritas'] }}</div><div class="sub"><a href="{{ route('admin.opiniones', ['tipo' => 'falla']) }}">ver con el botón Opinar</a></div><div class="ic"><i class="fa-solid fa-comment-dots"></i></div></div>
    </div>

    <div class="fa-filtros">
        <a href="{{ route('admin.fallas', array_filter(['resueltas' => $verResueltas ? '1' : null])) }}" class="{{ !$modulo ? 'on' : '' }}">Todo</a>
        @foreach (\App\Models\Opinion::MODULOS as $k => $n)
        <a href="{{ route('admin.fallas', array_filter(['modulo' => $k, 'resueltas' => $verResueltas ? '1' : null])) }}" class="{{ $modulo === $k ? 'on' : '' }}">{{ $n }}</a>
        @endforeach
        <a href="{{ route('admin.fallas', array_filter(['modulo' => $modulo, 'resueltas' => $verResueltas ? null : '1'])) }}">{{ $verResueltas ? 'Ocultar resueltas' : 'Ver también resueltas' }}</a>
    </div>

    @forelse ($errores as $f)
    @php $e = $f['e']; $x = $f['x']; @endphp
    <div class="fa-card {{ $e->resuelto ? 'res' : '' }}">
        <div class="fa-top">
            <div class="fa-ic">{{ $x['icono'] }}</div>
            <div class="flex-grow-1" style="min-width:0">
                <p class="fa-int">Intentaba: {{ $x['intento'] }}</p>
                <p class="fa-paso">{{ $x['paso'] }}</p>
                <div class="fa-meta">
                    <span class="rojo">{{ number_format($e->veces, 0, ',', '.') }} {{ $e->veces == 1 ? 'vez' : 'veces' }}</span>
                    <span>{{ number_format($e->usuarios, 0, ',', '.') }} {{ $e->usuarios == 1 ? 'persona' : 'personas' }}</span>
                    <span>📱 {{ $x['equipo'] }}</span>
                    <span>{{ \App\Models\Opinion::MODULOS[$e->modulo] ?? $e->modulo }}</span>
                    <span>Última: {{ $e->ultima_vez?->format('d/m h:i a') }}</span>
                    <span>Desde: {{ $e->primera_vez?->format('d/m/Y') }}</span>
                </div>
                @if ($x['det'])
                <div class="fa-det">
                    @foreach ($x['det'] as $k => $v)<div><b>{{ $k }}</b>{{ $v }}</div>@endforeach
                </div>
                @endif
                <details class="mt-1"><summary class="fa-id" style="cursor:pointer">Detalle técnico</summary>
                    <div class="fa-tec">Mensaje: {{ $e->mensaje }}
Dónde: {{ $e->donde ?: '—' }}
Página: {{ $e->pagina ?: '—' }}
Navegador: {{ $e->navegador }}

{{ $e->pila }}

{{ $e->datos ? json_encode($e->datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</div>
                </details>
                <div class="fa-pie">
                    <span class="fa-id">Falla E{{ $e->id }}{{ $e->resuelto ? ' · resuelta' : '' }}</span>
                    <form method="post" action="{{ route('admin.opiniones.error', $e) }}">@csrf<button class="btn btn-sm {{ $e->resuelto ? 'btn-outline-secondary' : 'btn-success' }}">{{ $e->resuelto ? 'Reabrir' : 'Marcar resuelta' }}</button></form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="lp-box text-center text-muted fw-semibold py-4">No hay fallas {{ $verResueltas ? '' : 'abiertas' }}. 🎉</div>
    @endforelse

    @if ($escritas->count())
    <div class="lp-box mt-3">
        <h3 class="mb-2"><i class="fa-solid fa-comment-dots me-1"></i>Fallas que la gente escribió (botón Opinar)</h3>
        @foreach ($escritas as $o)
        <div class="fa-card" style="border-left-color:#ff5c9a">
            <p class="fa-int mb-1">{{ \Illuminate\Support\Str::limit($o->mensaje, 300) }}</p>
            <div class="fa-meta">
                <span>{{ $o->user?->name ?? 'Sin cuenta' }}</span>
                @if ($o->tienda)<span>{{ $o->tienda->nombre }}</span>@endif
                <span>{{ \App\Models\Opinion::MODULOS[$o->modulo] ?? $o->modulo }}</span>
                <span>{{ $o->created_at?->format('d/m h:i a') }}</span>
                <span class="fa-id">#{{ $o->id }}</span>
            </div>
        </div>
        @endforeach
        <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.opiniones', ['tipo' => 'falla']) }}">Responder o cambiar estado</a>
    </div>
    @endif
</div>
@endsection
