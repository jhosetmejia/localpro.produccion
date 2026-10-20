@php $listaP = $programas->pluck('nombre', 'id')->all(); if ($sel && !isset($listaP[$sel])) $listaP[$sel] = $selNombre; $mitad = (int) ceil(count($listaP) / 2); $cols = array_chunk($listaP, max(1, $mitad), true); @endphp
<div class="progs">
    <div class="tl">PROGRAMAS</div>
    <div>@foreach ($cols[0] ?? [] as $id => $nom)<div><span class="chk">{{ (int) $id === (int) $sel ? 'X' : '' }}</span>{{ $nom }}</div>@endforeach</div>
    <div>@foreach ($cols[1] ?? [] as $id => $nom)<div><span class="chk">{{ (int) $id === (int) $sel ? 'X' : '' }}</span>{{ $nom }}</div>@endforeach</div>
</div>
