@extends('layouts.app')

@section('title', 'Columnas del archivo contable')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>{{ $p->cliente->nombre }} · {{ $p->nombre }}</small><h1>¿Qué es cada <span>columna</span>?</h1>
            <p class="text-muted mb-0">Ya marcamos lo que reconocimos. Revise y corrija; la próxima vez con el mismo programa contable lo recuerda.</p></div>
        <div class="lp-quick"><a href="{{ route('contabilidad.periodo', $p) }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a></div>
    </div>
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('contabilidad.mapeo.guardar', $p) }}">
        @csrf
        <input type="hidden" name="fila" value="{{ $v['fila'] }}">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="lp-box">
                    @foreach (\App\Support\Retenciones::CAMPOS_CONTABLES as $campo => [$nombre])
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-6 small fw-bold">{{ $nombre }} @if (in_array($campo, ['nit', 'documento'], true))<span class="text-danger">*</span>@endif</label>
                        <div class="col-6">
                            <select class="form-select form-select-sm" name="mapa[{{ $campo }}]">
                                <option value="">— No viene —</option>
                                @foreach ($v['titulos'] as $i => $t)
                                <option value="{{ $i }}" @selected(array_key_exists($campo, $v['mapa']) && (int) $v['mapa'][$campo] === $i)>{{ $t !== '' ? $t : 'Columna ' . ($i + 1) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endforeach
                    <div class="small text-muted mt-2">
                        <b>Dos formas sirven:</b><br>
                        · <b>En columnas</b>: una fila por factura con base, IVA y retenciones.<br>
                        · <b>Auxiliar por cuenta</b>: una fila por movimiento con la cuenta y el débito/crédito. Las cuentas 2365 se toman como retención en la fuente (2365 15 honorarios, 2365 25 servicios, 2365 30 arrendamientos, 2365 40 compras…), 2367 como reteIVA, 2368 como reteICA y 2408 como IVA.
                    </div>
                    <button class="btn btn-primary w-100 mt-3"><i class="fa-solid fa-code-compare me-1"></i>Cargar y cruzar con la DIAN</button>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="lp-box">
                    <h3>Así viene su archivo</h3>
                    <div class="table-responsive">
                        <table class="table table-sm small">
                            <thead><tr>@foreach ($v['titulos'] as $t)<th>{{ $t }}</th>@endforeach</tr></thead>
                            <tbody>
                            @foreach ($v['muestra'] as $fila)
                            <tr>@foreach ($v['titulos'] as $i => $t)<td class="text-nowrap">{{ \Illuminate\Support\Str::limit((string) ($fila[$i] ?? ''), 30) }}</td>@endforeach</tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
