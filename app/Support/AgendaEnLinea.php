<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Cita;
use App\Models\Tienda;
use App\Models\User;
use Carbon\Carbon;

// Citas en línea del consultorio: horario de atención, horas libres y cupos (uno por profesional de salud)
class AgendaEnLinea
{
    public const DIAS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    public static function config(Tienda $t): array
    {
        $a = fn ($k, $def) => Ajuste::valor('t' . $t->id . '_agenda_' . $k, $def);
        return [
            'activa' => (bool) (int) $a('online', 0),
            'dias' => array_map('intval', array_filter(explode(',', (string) $a('dias', '1,2,3,4,5,6')))),
            'desde' => (string) $a('desde', '08:00'),
            'hasta' => (string) $a('hasta', '18:00'),
            'pausa_desde' => (string) $a('pausa_desde', '12:00'),
            'pausa_hasta' => (string) $a('pausa_hasta', '14:00'),
            'anticipacion' => (int) $a('anticipacion', 2),   // horas mínimas antes de la cita
            'max_dias' => (int) $a('max_dias', 30),          // hasta cuántos días adelante
            'duracion' => (int) Ajuste::valor('t' . $t->id . '_cita_duracion', 30),
            'mensaje' => (string) $a('mensaje', ''),
        ];
    }

    public static function guardar(Tienda $t, array $d): void
    {
        $k = fn ($x) => 't' . $t->id . '_agenda_' . $x;
        Ajuste::guardar($k('online'), !empty($d['online']) ? '1' : '0');
        Ajuste::guardar($k('dias'), implode(',', array_map('intval', (array) ($d['dias'] ?? []))));
        foreach (['desde', 'hasta', 'pausa_desde', 'pausa_hasta', 'anticipacion', 'max_dias', 'mensaje'] as $x) {
            if (array_key_exists($x, $d)) Ajuste::guardar($k($x), (string) ($d[$x] ?? ''));
        }
    }

    public static function cupos(Tienda $t): int
    {
        return max(1, User::where('tienda_id', $t->id)->get()->filter(fn ($u) => $u->can('historia-clinica') && !$u->es_superadmin)->count());
    }

    /** Horas libres de un día (formato H:i). Llamar con TiendaActual ya puesta en la tienda. */
    public static function horasLibres(Tienda $t, Carbon $dia): array
    {
        $c = self::config($t);
        $dia = $dia->copy()->startOfDay();
        if (!$c['activa'] || !in_array($dia->dayOfWeekIso, $c['dias'], true)) return [];
        if ($dia->lt(today()) || $dia->gt(today()->addDays($c['max_dias']))) return [];
        $dur = max(10, $c['duracion']);
        $minimo = now()->addHours(max(0, $c['anticipacion']));
        $citas = Cita::whereDate('fecha', $dia->toDateString())->whereIn('estado', ['programada', 'confirmada'])->get(['hora', 'duracion']);
        $cupos = self::cupos($t);
        $out = [];
        $h = Carbon::parse($dia->toDateString() . ' ' . $c['desde']);
        $fin = Carbon::parse($dia->toDateString() . ' ' . $c['hasta']);
        $pd = $c['pausa_desde'] ? Carbon::parse($dia->toDateString() . ' ' . $c['pausa_desde']) : null;
        $ph = $c['pausa_hasta'] ? Carbon::parse($dia->toDateString() . ' ' . $c['pausa_hasta']) : null;
        for ($i = 0; $i < 200 && $h->copy()->addMinutes($dur)->lte($fin); $i++, $h->addMinutes($dur)) {
            $f = $h->copy()->addMinutes($dur);
            if ($h->lt($minimo)) continue;
            if ($pd && $ph && $pd->lt($ph) && $h->lt($ph) && $f->gt($pd)) continue;
            $ocupadas = $citas->filter(function ($x) use ($dia, $h, $f) {
                $ci = Carbon::parse($dia->toDateString() . ' ' . $x->hora);
                return $ci->lt($f) && $ci->copy()->addMinutes((int) $x->duracion)->gt($h);
            })->count();
            if ($ocupadas < $cupos) $out[] = $h->format('H:i');
        }
        return $out;
    }

    // Próximos días con horas libres (para mostrar en la página)
    public static function diasDisponibles(Tienda $t, int $cuantos = 14): array
    {
        $c = self::config($t);
        $out = [];
        for ($i = 0; $i <= $c['max_dias'] && count($out) < $cuantos; $i++) {
            $d = today()->addDays($i);
            if (!in_array($d->dayOfWeekIso, $c['dias'], true)) continue;
            $n = count(self::horasLibres($t, $d));
            if ($n) $out[] = ['fecha' => $d->toDateString(), 'dia' => self::DIAS[$d->dayOfWeekIso], 'num' => $d->day, 'mes' => ucfirst($d->translatedFormat('M')), 'libres' => $n];
        }
        return $out;
    }
}
