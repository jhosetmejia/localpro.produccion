{{--
    Lienzo del obituario (lo que sale en el televisor de la sala).
    $p: ObituarioPlantilla · $o: Obituario o null (sale con datos de ejemplo) · $tienda: Tienda
--}}
@once
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" crossorigin="anonymous" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,600;1,700&family=Great+Vibes&family=Montserrat:ital,wght@0,500;0,700;0,800;1,500&display=swap">
<link rel="stylesheet" href="{{ asset('css/obituarios.css') }}?v=8">
@endonce
@php
    $m = $p->medida_datos;
    $o = $o ?? null;
    $d = $o ? [
        'nombre' => $o->nombre,
        'foto' => $o->foto_url,
        'anios' => $o->anios,
        'frase' => $o->frase ?: $p->frase,
        'sala' => $o->sala?->nombre,
        'fecha' => $o->fecha_texto,
        'hora' => $o->hora_texto,
        'lugar' => $o->lugar_exequias,
        'destino' => $o->destino_final,
        'grupos' => \App\Models\Obituario::grupos($o->invitan),
    ] : [
        'nombre' => 'Nombre del fallecido',
        'foto' => null,
        'anios' => '07-12-1950 / ' . now()->format('d-m-Y'),
        'frase' => $p->frase,
        'sala' => 'Proyector 1',
        'fecha' => \App\Models\Obituario::fechaLarga(now()->addDay()),
        'hora' => '3 p. m.',
        'lugar' => 'Parroquia Nuestra Señora del Carmen',
        'destino' => 'Cementerio Central',
        'grupos' => [['Su esposa', 'Nombre de la esposa'], ['Sus hijos', 'Nombres de los hijos'], ['Sus hermanos', 'Nombres de los hermanos'], ['Demás familiares y amigos', '']],
    ];
    $fondoCss = '';
    if ($p->fondo_tipo === 'color' && $p->fondo_color) {
        $fondoCss = 'background:' . ($p->fondo_color2 ? "linear-gradient(160deg,{$p->fondo_color},{$p->fondo_color2})" : $p->fondo_color);
    } elseif ($p->fondo_tipo === 'imagen' && $p->fondo_url) {
        $fondoCss = "background:url('" . e($p->fondo_url) . "') center/cover no-repeat";
    }
    $video = $p->fondo_tipo === 'video' ? $p->video_url : null;
    $velo = in_array($p->fondo_tipo, ['imagen', 'video'], true) ? $p->oscurecer / 100 : 0;
    // Logo propio de los obituarios (sin fondo) o, si no hay, el del negocio
    $logo = $tienda->obituarios_logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($tienda->obituarios_logo_path) : $tienda->logo_url;
    $conLogo = $p->mostrar_logo;
    $fe = \App\Models\Obituario::fotoEstilo($o);
    $tarjeta = $p->es_tarjeta;
    $fl = $p->flores_datos;
    $an = $p->anim_datos;
    // Colores propios también en las tarjetas prediseñadas
    $colTarjeta = ($p->color_acento ? ';--t-acento:' . $p->color_acento : '') . ($p->color_texto ? ';--t-texto:' . $p->color_texto . ';--t-titulo:' . $p->color_texto : '');
@endphp
<div class="obi d-{{ $p->diseno }} o-{{ $m['orientacion'] }} adornos-{{ $p->adornos ?: 'muchos' }} {{ $tarjeta ? 'lay-tarjeta' : '' }} flor-{{ $fl['pos'] }} esq-{{ $fl['esq'] }} {{ $p->fondo_tipo !== 'diseno' ? 'fondo-propio' : '' }}" data-obi
     data-w="{{ $m['base_ancho'] }}" data-h="{{ $m['base_alto'] }}" data-escala="{{ $m['escala'] }}" data-anim="{{ $p->animacion }}" data-anim-cant="{{ $an['cant'] }}" data-anim-tam="{{ $an['tam'] }}" data-anim-vel="{{ $an['vel'] }}"
     style="width:{{ $m['base_ancho'] }}px;height:{{ $m['base_alto'] }}px;--acento:{{ $p->acento }};--texto:{{ $p->texto_color }};--fx:{{ $fl['x'] }}px;--fy:{{ $fl['y'] }}px;--ft:{{ $fl['tam'] / 100 }}{{ $colTarjeta }}">
    <div class="obi-fondo" style="{{ $fondoCss }}">@if ($video)<video src="{{ $video }}" autoplay muted loop playsinline></video>@endif</div>
    <div class="obi-velo" style="opacity:{{ $velo }}"></div>
    <div class="obi-marco-deco"></div>
    <div class="obi-anim-capa" data-anim-capa></div>
    <div class="obi-esq e1"></div><div class="obi-esq e2"></div><div class="obi-esq e3"></div><div class="obi-esq e4"></div>
    <div class="obi-ramo r-c1">@include('obituarios._ramo')</div>
    <div class="obi-ramo r-c2">@include('obituarios._ramo')</div>
    <div class="obi-ramo r-c3">@include('obituarios._ramo')</div>
    <div class="obi-ramo r-c4">@include('obituarios._ramo')</div>
    <div class="obi-cuerpo">
        <section class="obi-lado">
            <div class="obi-foto-wrap">
                <div class="obi-ramo r-f1">@include('obituarios._ramo')</div>
                <div class="obi-foto {{ $fe['clase'] }}" style="{{ $fe['estilo'] }}">
                    <svg viewBox="0 0 100 100" @if ($d['foto']) style="display:none" @endif><circle cx="50" cy="38" r="17" fill="#fff" opacity=".85"/><path d="M18 92c2-20 16-30 32-30s30 10 32 30z" fill="#fff" opacity=".85"/></svg>
                    @if ($d['foto'])<img src="{{ $d['foto'] }}" alt="">@endif
                </div>
                <div class="obi-ramo r-f2">@include('obituarios._ramo')</div>
            </div>
            <div class="obi-memoria"><span class="obi-script">En memoria de</span><b data-o="nombre">{{ $d['nombre'] }}</b></div>
        </section>
        <section class="obi-centro">
            <div class="obi-esq e1"></div><div class="obi-esq e2"></div><div class="obi-esq e3"></div><div class="obi-esq e4"></div>
            <header class="obi-cab">
                <img class="obi-logo" src="{{ $logo }}" alt="" @if ($logo) data-logo @unless ($conLogo) hidden @endunless @else data-logo-vacio hidden @endif>
                <div class="obi-empresa" data-empresa @if ($logo) data-sin-logo @if ($conLogo) hidden @endif @else data-logo @unless ($conLogo) hidden @endunless @endif>{{ $tienda->nombre }}</div>
            </header>
            <div class="obi-titulo" data-o="titulo" data-fila="titulo" @if (!$p->titulo) hidden @endif>{{ $p->titulo }}</div>
            <div class="obi-sub">
                <span class="obi-sala" data-o="sala" data-fila="sala" @if (!$d['sala']) hidden @endif>{{ $d['sala'] }}</span>
                <span class="obi-frase" data-o="frase" data-fila="frase" @if (!$d['frase']) hidden @endif>{{ $d['frase'] }}</span>
            </div>
            <div class="obi-linea"></div>
            <h1 class="obi-nombre" data-o="nombre">{{ $d['nombre'] }}</h1>
            <div class="obi-anios" data-o="anios" data-fila="anios" @if (!$d['anios']) hidden @endif>{{ $d['anios'] }}</div>
            <div class="obi-datos">
                <div class="obi-dato" data-fila="fecha" @if (!$d['fecha']) hidden @endif><span class="obi-et">Fecha</span><span class="obi-val" data-o="fecha">{{ $d['fecha'] }}</span></div>
                <div class="obi-dato" data-fila="lugar" @if (!$d['lugar']) hidden @endif><span class="obi-et">Exequias</span><span class="obi-val" data-o="lugar">{{ $d['lugar'] }}</span></div>
                <div class="obi-dato" data-fila="hora" @if (!$d['hora']) hidden @endif><span class="obi-et">Hora</span><span class="obi-val" data-o="hora">{{ $d['hora'] }}</span></div>
                <div class="obi-dato" data-fila="destino" @if (!$d['destino']) hidden @endif><span class="obi-et">Destino final</span><span class="obi-val" data-o="destino">{{ $d['destino'] }}</span></div>
            </div>
            <div class="obi-invitan" data-fila="invitan" @if (!$d['grupos']) hidden @endif>
                <span class="obi-et">Invitan</span>
                <div class="obi-grupos" data-o="grupos">
                    @foreach ($d['grupos'] as [$gt, $gn])
                    <div class="obi-grupo"><b>{{ $gt }}</b>@if ($gn)<span>{{ $gn }}</span>@endif</div>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="obi-der">
            <img class="obi-logo" src="{{ $logo }}" alt="" @if ($logo) data-logo @unless ($conLogo) hidden @endunless @else data-logo-vacio hidden @endif>
            <div class="obi-empresa" data-empresa @if ($logo) data-sin-logo @if ($conLogo) hidden @endif @endif>{{ $tienda->nombre }}</div>
            <div class="obi-ramo r-d">@include('obituarios._ramo')</div>
            <div class="obi-pie" data-o="pie" data-fila="pie" @if (!$p->pie) hidden @endif>{{ $p->pie }}</div>
        </section>
        <div class="obi-pie-v" data-o="pie" data-fila="pie" @if (!$p->pie) hidden @endif>{{ $p->pie }}</div>
    </div>
    @include('obituarios._tarjeta')
</div>
