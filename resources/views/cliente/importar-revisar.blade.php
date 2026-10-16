@extends('layouts.app')

@section('title', 'Revisar clientes')

@push('css')
<style>
.ir-res{display:flex;gap:.8rem;flex-wrap:wrap;margin-bottom:1rem}
.ir-res div{flex:1 1 150px;background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.9rem 1rem}
.ir-res div.v{background:#0b0d0c;color:#fff;border:0}
.ir-res b{display:block;font-size:1.7rem;font-weight:900;line-height:1.1}
.ir-res .v b{color:#2fd15a}
.ir-res small{color:#6b7570;font-weight:700}.ir-res .v small{color:#c9d3cc}
.ir-map{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.7rem}
.ir-map label{display:block;font-weight:800;font-size:.85rem;margin-bottom:.25rem}
.ir-map .ok{color:#16b34a}.ir-map .no{color:#a3aca6}
.ir-tabla td,.ir-tabla th{font-size:.88rem;white-space:nowrap;max-width:220px;overflow:hidden;text-overflow:ellipsis}
.ir-tabla td.vacio{color:#c2cbc5}
.ir-acc{display:flex;gap:.7rem;flex-wrap:wrap;margin-top:1rem}
.ir-btn{border:0;border-radius:999px;padding:.9rem 1.6rem;font-weight:900;cursor:pointer}
.ir-btn.g{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c;flex:1 1 260px;font-size:1.05rem}
.ir-btn.l{background:#f1f5f2;color:#0b0d0c}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-file-excel me-1"></i>{{ $archivo }}</small><h1>Esto encontramos en <span>su Excel</span></h1>
            <span class="text-muted fw-semibold">Revise que cada dato esté en su lugar y toque "Crear clientes".</span></div>
    </div>
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (session('success'))<div class="alert alert-success fw-bold">{{ session('success') }}</div>@endif
    @if ($parte['partes'] > 1)
    <div class="alert fw-bold" style="background:#fffbeb;border:1.5px solid #fde68a;color:#78501a;border-radius:16px">
        📦 Su archivo tiene {{ number_format($parte['total'], 0, ',', '.') }} contactos. Los importamos por partes de 5.000:
        esta es la <b>parte {{ $parte['n'] }} de {{ $parte['partes'] }}</b> (contactos {{ number_format($parte['desde'], 0, ',', '.') }} a {{ number_format($parte['hasta'], 0, ',', '.') }}).
        Al terminar le mostramos la siguiente.
    </div>
    @elseif (session('info'))<div class="alert alert-info fw-bold">{{ session('info') }}</div>@endif

    <div class="ir-res">
        <div class="v"><b>{{ number_format($validos, 0, ',', '.') }}</b><small>clientes listos para crear</small></div>
        <div><b>{{ number_format($conTel, 0, ',', '.') }}</b><small>con celular (para WhatsApp)</small></div>
        <div><b>{{ number_format($conMail, 0, ',', '.') }}</b><small>con correo</small></div>
        <div><b>{{ number_format($total - $validos, 0, ',', '.') }}</b><small>filas sin nombre o sin datos de contacto (se saltan)</small></div>
    </div>

    <form method="post" action="{{ route('clientes.importar.confirmar') }}" id="f-imp">
        @csrf
        <div class="lp-box mb-3">
            <h3><span><i class="fa-solid fa-table-columns me-1"></i> ¿Qué columna es cada dato?</span></h3>
            <div class="ir-map">
                @foreach (\App\Support\ImportarClientes::CAMPOS as $campo => $nombre)
                <div>
                    <label>@if (isset($mapa[$campo]))<i class="fa-solid fa-circle-check ok me-1"></i>@else<i class="fa-regular fa-circle no me-1"></i>@endif{{ $nombre }}</label>
                    <select name="m[{{ $campo }}]" class="form-select" data-nativo onchange="var q=new URLSearchParams(new FormData(this.form));q.delete('_token');location='{{ route('clientes.importar.revisar') }}?'+q.toString()">
                        <option value="">— No tiene —</option>
                        @foreach ($titulos as $i => $t)<option value="{{ $i }}" @selected(isset($mapa[$campo]) && (int) $mapa[$campo] === $i)>{{ $t !== '' ? $t : 'Columna ' . \App\Support\ImportarClientes::letra($i) }}</option>@endforeach
                    </select>
                </div>
                @endforeach
            </div>
        </div>

        <div class="lp-box">
            <h3><span><i class="fa-solid fa-eye me-1"></i> Así van a quedar (primeros {{ count($muestra) }})</span></h3>
            <div class="table-responsive">
                <table class="table ir-tabla align-middle mb-0">
                    <thead><tr><th>Nombre</th><th>Cédula</th><th>Celular</th><th>Correo</th><th>Dirección</th></tr></thead>
                    <tbody>
                    @foreach ($muestra as $c)
                    <tr>
                        @foreach (['nombre', 'documento', 'telefono', 'email', 'direccion'] as $k)
                        <td class="{{ $c[$k] === '' ? 'vacio' : '' }}">{{ $c[$k] !== '' ? $c[$k] : '—' }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="ir-acc">
                <button class="ir-btn g" @disabled(!$validos || !isset($mapa['nombre']))><i class="fa-solid fa-user-plus me-1"></i>Crear {{ number_format($validos, 0, ',', '.') }} clientes</button>
                <button type="submit" form="f-cancel" class="ir-btn l">Subir otro archivo</button>
            </div>
            @if (!isset($mapa['nombre']))<p class="text-danger fw-bold small mt-2 mb-0">Elija arriba cuál columna tiene el nombre.</p>@endif
            <p class="text-muted small mt-2 mb-0">Los clientes que ya tiene (misma cédula, celular o correo) no se repiten.</p>
        </div>
    </form>
    <form method="post" action="{{ route('clientes.importar.cancelar') }}" id="f-cancel">@csrf</form>
</div>
@endsection
