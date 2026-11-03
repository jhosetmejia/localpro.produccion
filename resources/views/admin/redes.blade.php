@extends('layouts.app')

@section('title', 'Redes de los negocios')

@section('content')
<style>
.ar-row{display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:center}
.ar-ch{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.4rem}
.ar-ch span{font-size:.78rem;font-weight:700;border-radius:999px;padding:.15rem .6rem;background:#f1f5f2;display:inline-flex;gap:.3rem;align-items:center}
.ar-ch .err{background:#ffe9f3;color:#b0173f}
.ar-acc{display:flex;gap:.4rem;flex-wrap:wrap;justify-content:flex-end}
@media (max-width:760px){.ar-row{grid-template-columns:1fr}.ar-acc{justify-content:flex-start}}
</style>
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Redes de los <span>negocios</span></h1>
            <span class="text-muted fw-semibold">Todas las páginas de Facebook, Instagram y canales de YouTube conectados. Entre a publicar o responder por cualquier negocio.</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form class="mb-3 d-flex gap-2" method="get" style="max-width:520px">
        <input class="form-control" name="q" value="{{ $q }}" placeholder="Buscar negocio o código LCT">
        <button class="btn btn-dark"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    @forelse ($tiendas as $t)
    @php $p = $pubs[$t->id] ?? null; @endphp
    <div class="lp-box mb-2 ar-row">
        <div>
            <b>{{ $t->nombre }}</b> <small class="text-muted">{{ $t->codigo }}</small>
            <div class="ar-ch">
                @foreach ($cuentas[$t->id] as $c)
                <span class="{{ $c->estado === 'error' ? 'err' : '' }}" title="{{ $c->error }}">
                    @if ($c->es_youtube)<i class="fa-brands fa-youtube" style="color:#ff0000"></i>@elseif ($c->es_correo)<i class="fa-solid fa-envelope" style="color:#ea4335"></i>@else<i class="fa-brands fa-facebook" style="color:#1877f2"></i>@if ($c->ig_usuario) <i class="fa-brands fa-instagram" style="color:#e91e63"></i>@endif @endif
                    {{ $c->page_nombre }}{{ $c->ig_usuario && !$c->es_youtube ? ' · @' . $c->ig_usuario : '' }}{{ $c->estado === 'error' ? ' · con error' : '' }}
                </span>
                @endforeach
            </div>
            @if ($p)
            <small class="text-muted d-block mt-1">
                {{ $p->ultima ? 'Última publicación: ' . \Illuminate\Support\Carbon::parse($p->ultima)->locale('es')->diffForHumans() : '' }}
                {{ (int) $p->prog ? ' · ' . (int) $p->prog . ' programadas' : '' }}{{ (int) $p->subiendo ? ' · ' . (int) $p->subiendo . ' subiendo' : '' }}{{ (int) $p->mal ? ' · ' . (int) $p->mal . ' con error' : '' }}
            </small>
            @endif
        </div>
        <div class="ar-acc">
            <form method="post" action="{{ route('admin.redes.abrir', $t) }}">@csrf<input type="hidden" name="ir" value="publicar"><button class="btn btn-primary"><i class="fa-regular fa-paper-plane me-1"></i>Publicar</button></form>
            <form method="post" action="{{ route('admin.redes.abrir', $t) }}">@csrf<input type="hidden" name="ir" value="mensajes"><button class="btn btn-light"><i class="fa-regular fa-comments me-1"></i>Mensajes</button></form>
            <form method="post" action="{{ route('admin.redes.abrir', $t) }}">@csrf<input type="hidden" name="ir" value="cuentas"><button class="btn btn-light"><i class="fa-solid fa-link"></i></button></form>
        </div>
    </div>
    @empty
    <div class="lp-box text-muted">{{ $q !== '' ? 'Ningún negocio con redes coincide con la búsqueda.' : 'Ningún negocio ha conectado sus redes todavía.' }}</div>
    @endforelse
</div>
@endsection
