@extends('layouts.app')

@section('title', 'Mis certificados')

@section('content')
@php
    $n = count($cursos);
    $pct = min(100, (int) round($n / max(1, $minimo) * 100));
    $puede = empty($falta);
@endphp
<div class="lp-dash">
    @include('layouts.partials.lp-flash')
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-award me-1"></i>Red comercial</small><h1>Mis <span>certificados</span></h1></div>
    </div>

    <div class="lp-box mb-3">
        <div class="d-flex align-items-start gap-3 flex-wrap">
            <div style="width:52px;height:52px;border-radius:16px;background:#0b0d0c;color:#a6f34a;display:grid;place-items:center;flex:none"><i class="fa-solid fa-file-signature fa-lg"></i></div>
            <div style="flex:1;min-width:220px">
                <h5 class="mb-1" style="font-weight:800">Constancia de experiencia como Asesor Comercial LocalPro</h5>
                <p class="mb-2 text-muted" style="font-weight:600">Para presentar en un trabajo o en su hoja de vida: dice desde cuándo es asesor de LocalPro, qué hace, sus resultados y los cursos que aprobó. Trae un código QR para que la empresa verifique que es real.</p>
                <div style="height:10px;border-radius:99px;background:#eef3ef;overflow:hidden"><div style="height:100%;width:{{ $pct }}%;background:linear-gradient(135deg,#a6f34a,#16b34a)"></div></div>
                <small class="d-block mt-1" style="font-weight:700">{{ min($n, $minimo) }} de {{ $minimo }} cursos aprobados en Aprende LocalPro</small>

                @if ($puede)
                <a href="{{ route('asesor.certificados.constancia') }}" class="btn btn-success mt-3" style="border-radius:999px;font-weight:800"><i class="fa-solid fa-file-arrow-down me-1"></i> Descargar constancia en PDF</a>
                @else
                <div class="mt-3 p-3" style="background:#fff7e6;border-radius:14px;color:#7a4b00;font-weight:700">
                    <i class="fa-solid fa-lock me-1"></i> Para descargarla debe cursar al menos {{ $minimo }} cursos en LocalPro. Le falta: {{ implode(' y ', $falta) }}.
                </div>
                <div class="d-flex gap-2 flex-wrap mt-2">
                    <a href="{{ route('aprende.index') }}" class="btn btn-dark" style="border-radius:999px;font-weight:800"><i class="fa-solid fa-book-open me-1"></i> Ir a los cursos</a>
                    @if (!filled($yo->documento))
                    <a href="{{ route('asesor.cuenta.edit') }}" class="btn btn-light" style="border-radius:999px;font-weight:800"><i class="fa-solid fa-id-card me-1"></i> Poner mi cédula</a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="lp-box mb-3">
        <h6 style="font-weight:800" class="mb-2"><i class="fa-solid fa-graduation-cap me-1"></i> Mis cursos</h6>
        <ul class="lp-list">
            <li><div class="av"><i class="fa-solid fa-user-graduate"></i></div>
                <div class="t"><b>Curso Básico de Asesores Comerciales</b><small>{{ $yo->curso_aprobado_at ? 'Aprobado el ' . $yo->curso_aprobado_at->format('d/m/Y') . ' · ' . $yo->certificado : 'Pendiente' }}</small></div>
                @if ($yo->curso_aprobado_at)<a href="{{ route('curso.certificado') }}" class="btn btn-sm btn-light" style="border-radius:999px;font-weight:700"><i class="fa-solid fa-award me-1"></i>Ver certificado</a>@endif
            </li>
            @forelse ($cursos as $c)
            <li><div class="av"><i class="fa-solid fa-circle-check"></i></div>
                <div class="t"><b>{{ $c['titulo'] }}</b><small>Aprobado el {{ $c['fecha']->format('d/m/Y') }} · cuenta para la constancia</small></div></li>
            @empty
            <li class="lp-empty">Todavía no ha aprobado cursos de Aprende LocalPro. Son cortos (2 a 4 minutos por lección) y se hacen desde el celular.</li>
            @endforelse
        </ul>
    </div>

    <div class="lp-box" style="background:#f6f8f7">
        <small style="font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#5b665e"><i class="fa-solid fa-scale-balanced me-1"></i> Importante</small>
        <p class="mb-0 mt-1" style="font-size:.88rem;color:#5b665e;font-weight:600">{{ \App\Support\CertificadoAsesor::NOTA_LEGAL }}</p>
    </div>
</div>
@endsection
