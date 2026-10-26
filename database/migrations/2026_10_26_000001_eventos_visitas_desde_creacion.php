<?php

use App\Models\Evento;
use App\Models\EventoEntrada;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Visitas desde que se creó cada evento.
// El contador empezó a funcionar después de que algunos eventos ya estaban publicados,
// así que esas páginas muestran menos visitas de las que tuvieron.
// Todo el que se registró a comprar entró a la página, así que el contador nunca debe quedar
// por debajo de las personas registradas. Esta migración lo corrige con datos reales, sin inventar.
return new class extends Migration
{
    public function up(): void
    {
        $tEv = (new Evento)->getTable();
        $tEn = (new EventoEntrada)->getTable();
        if (!Schema::hasTable($tEv) || !Schema::hasTable($tEn) || !Schema::hasColumn($tEv, 'visitas')) return;

        // Una persona por comprador (quien compra para sus amigos cuenta como una visita)
        $porPersona = Schema::hasColumn($tEn, 'asistente_id');

        $conteos = DB::table($tEn)
            ->select('evento_id', DB::raw($porPersona ? 'COUNT(DISTINCT asistente_id) AS n' : 'COUNT(*) AS n'))
            ->groupBy('evento_id')
            ->pluck('n', 'evento_id');

        foreach ($conteos as $eventoId => $n) {
            DB::table($tEv)->where('id', $eventoId)->where('visitas', '<', (int) $n)->update(['visitas' => (int) $n]);
        }
    }

    public function down(): void
    {
        // No se puede saber cuántas visitas había antes: se deja como está.
    }
};
