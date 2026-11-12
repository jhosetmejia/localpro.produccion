@extends('layouts.app')

@section('title', 'Marketplace')

@section('content')
@php $dom = config('app.dominio'); $mkt = route('marketplace'); $mktVer = app()->environment('production') && $dom ? 'https://www.' . $dom . parse_url($mkt, PHP_URL_PATH) : $mkt; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Marketplace <span>y tiendas</span></h1>
            <span class="text-muted fw-semibold">Elija cómo se ven el marketplace y todas las tiendas en línea de los negocios. El cambio se ve de una vez.</span></div>
        <div class="lp-quick"><a href="{{ $mktVer }}" target="_blank" rel="noopener" class="btn btn-light"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Ver el marketplace</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('admin.marketplace.guardar') }}" class="lp-box" style="max-width:820px">@csrf
        <h3><i class="fa-solid fa-palette me-1"></i>Tema del marketplace y las tiendas</h3>
        <div class="row g-3">
            @foreach ($temas as $k => [$nom, $txt, $fondo, $acento])
            <div class="col-md-6">
                <label class="d-block h-100" style="border:2px solid {{ $tema === $k ? '#16b34a' : '#e3ebe6' }};border-radius:18px;cursor:pointer;overflow:hidden">
                    <div style="height:110px;background:{{ $k === 'navidad' ? 'radial-gradient(90% 120% at 90% 0%,rgba(230,57,70,.45),transparent 60%),linear-gradient(180deg,#0f2a1d,#173b29)' : 'linear-gradient(180deg,#0b0d0c,#121a15)' }};color:#fff;padding:14px 16px;position:relative">
                        <b style="font-size:1.05rem">LocalProo <small style="color:{{ $k === 'navidad' ? '#ffd166' : '#a6f34a' }}">Marketplace</small></b>
                        <div style="margin-top:10px;display:flex;gap:6px">
                            <span style="width:46px;height:46px;border-radius:12px;background:#fff"></span><span style="width:46px;height:46px;border-radius:12px;background:#fff"></span><span style="width:46px;height:46px;border-radius:12px;background:#fff"></span>
                        </div>
                        @if ($k === 'navidad')<span style="position:absolute;right:12px;bottom:4px;font-size:2.6rem">🎄</span><span style="position:absolute;left:0;right:0;top:0;height:6px;background:repeating-linear-gradient(90deg,#ff4d5e 0 10px,#ffd166 10px 20px,#3ddc84 20px 30px,#5fb4ff 30px 40px)"></span>@endif
                    </div>
                    <div class="p-3">
                        <input type="radio" name="tema" value="{{ $k }}" class="form-check-input me-1" @checked($tema === $k)>
                        <b>{{ $nom }}</b> @if ($tema === $k)<span class="badge bg-success ms-1">En uso</span>@endif
                        <small class="text-muted d-block">{{ $txt }}</small>
                    </div>
                </label>
            </div>
            @endforeach
        </div>
        <small class="text-muted d-block mt-3">Aplica al marketplace y a la tienda en línea de cada negocio (página principal y productos). La carta de los restaurantes la elige cada negocio en Datos del negocio (allí también está el tema Navidad).</small>
        <button class="btn btn-primary mt-3"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar tema</button>
    </form>
</div>
@endsection
