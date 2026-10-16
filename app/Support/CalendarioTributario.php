<?php

namespace App\Support;

use App\Models\ContableCliente;
use App\Models\ContablePeriodo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendario tributario nacional: vencimientos de la retención en la fuente (Formulario 350) y del IVA (Formulario 300)
 * según el último dígito del NIT (sin dígito de verificación).
 *
 * Fuente: Decreto 1625 de 2016 (DUR tributario), arts. 1.6.1.13.2.33 (retención), 1.6.1.13.2.30 (IVA bimestral) y
 * 1.6.1.13.2.31 (IVA cuatrimestral), modificados por el art. 1 del Decreto 2229 de 2023.
 * El IVA bimestral y el cuatrimestral vencen el mismo día que la retención en la fuente de ese mes.
 *
 * Cada año la DIAN publica el calendario siguiente (normalmente en diciembre): se agrega aquí una fila por mes.
 */
class CalendarioTributario
{
    /** Mes en que vence (AAAA-MM) => día de vencimiento por último dígito del NIT */
    public const DIAS = [
        '2026-02' => [1 => 10, 2 => 11, 3 => 12, 4 => 13, 5 => 16, 6 => 17, 7 => 18, 8 => 19, 9 => 20, 0 => 23],
        '2026-03' => [1 => 10, 2 => 11, 3 => 12, 4 => 13, 5 => 16, 6 => 17, 7 => 18, 8 => 19, 9 => 20, 0 => 24],
        '2026-04' => [1 => 13, 2 => 14, 3 => 15, 4 => 16, 5 => 20, 6 => 21, 7 => 22, 8 => 23, 9 => 24, 0 => 27],
        '2026-05' => [1 => 12, 2 => 13, 3 => 14, 4 => 15, 5 => 19, 6 => 20, 7 => 21, 8 => 22, 9 => 25, 0 => 26],
        '2026-06' => [1 => 10, 2 => 11, 3 => 12, 4 => 16, 5 => 17, 6 => 18, 7 => 19, 8 => 22, 9 => 23, 0 => 24],
        '2026-07' => [1 => 9, 2 => 10, 3 => 13, 4 => 14, 5 => 15, 6 => 16, 7 => 17, 8 => 21, 9 => 22, 0 => 23],
        '2026-08' => [1 => 12, 2 => 13, 3 => 14, 4 => 18, 5 => 19, 6 => 20, 7 => 21, 8 => 24, 9 => 25, 0 => 26],
        '2026-09' => [1 => 9, 2 => 10, 3 => 11, 4 => 14, 5 => 15, 6 => 16, 7 => 17, 8 => 18, 9 => 21, 0 => 22],
        '2026-10' => [1 => 9, 2 => 13, 3 => 14, 4 => 15, 5 => 16, 6 => 19, 7 => 20, 8 => 21, 9 => 22, 0 => 23],
        '2026-11' => [1 => 11, 2 => 12, 3 => 13, 4 => 17, 5 => 18, 6 => 19, 7 => 20, 8 => 23, 9 => 24, 0 => 25],
        '2026-12' => [1 => 10, 2 => 11, 3 => 14, 4 => 15, 5 => 16, 6 => 17, 7 => 18, 8 => 21, 9 => 22, 0 => 23],
        '2027-01' => [1 => 13, 2 => 14, 3 => 15, 4 => 18, 5 => 19, 6 => 20, 7 => 21, 8 => 22, 9 => 25, 0 => 26],
    ];

    public const FUENTE = 'Decreto 1625 de 2016, arts. 1.6.1.13.2.30, 1.6.1.13.2.31 y 1.6.1.13.2.33, modificados por el Decreto 2229 de 2023 (calendario 2026).';

    public const TIPOS = [
        '350' => ['Retención en la fuente', 'Formulario 350', 'fa-file-invoice-dollar'],
        'iva' => ['IVA', 'Formulario 300', 'fa-percent'],
    ];

    public static function digito(?string $nit): ?int
    {
        $n = preg_replace('/\D/', '', (string) $nit);
        return $n === '' ? null : (int) substr($n, -1);
    }

    public static function fecha(string $venceMes, ?int $digito): ?Carbon
    {
        if ($digito === null || !isset(self::DIAS[$venceMes][$digito])) return null;
        return Carbon::parse($venceMes . '-01')->day(self::DIAS[$venceMes][$digito])->startOfDay();
    }

    /** Último mes de vencimiento publicado (para avisar cuando falta el calendario del año siguiente) */
    public static function ultimoMes(): Carbon
    {
        return Carbon::parse(array_key_last(self::DIAS) . '-01')->endOfMonth();
    }

    private static function mes(int $m): string
    {
        return ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'][$m];
    }

    /**
     * Vencimientos de un cliente con fecha entre $desde y $hasta.
     * Cada uno: tipo, nombre, formulario, periodo (texto), anio y mes del periodo, fecha.
     */
    public static function deCliente(ContableCliente $c, Carbon $desde, Carbon $hasta): array
    {
        $dig = self::digito($c->nit);
        $out = [];
        foreach (self::DIAS as $vence => $dias) {
            $f = self::fecha($vence, $dig);
            if (!$f || $f->lt($desde->copy()->startOfDay()) || $f->gt($hasta->copy()->endOfDay())) continue;
            $per = Carbon::parse($vence . '-01')->subMonth(); // el periodo que se declara es el mes anterior
            $y = $per->year; $m = $per->month;

            if ($c->agente_retenedor || $c->autorretenedor) {
                $out[] = ['tipo' => '350', 'nombre' => self::TIPOS['350'][0], 'formulario' => self::TIPOS['350'][1],
                    'periodo' => self::mes($m) . ' ' . $y, 'anio' => $y, 'mes' => $m, 'fecha' => $f];
            }
            if ($c->periodicidad_iva === 'bimestral' && $m % 2 === 0) {
                $out[] = ['tipo' => 'iva', 'nombre' => 'IVA bimestral', 'formulario' => self::TIPOS['iva'][1],
                    'periodo' => 'Bimestre ' . ($m / 2) . ' (' . self::mes($m - 1) . '–' . self::mes($m) . ' ' . $y . ')', 'anio' => $y, 'mes' => $m, 'fecha' => $f];
            }
            if ($c->periodicidad_iva === 'cuatrimestral' && $m % 4 === 0) {
                $out[] = ['tipo' => 'iva', 'nombre' => 'IVA cuatrimestral', 'formulario' => self::TIPOS['iva'][1],
                    'periodo' => 'Cuatrimestre ' . ($m / 4) . ' (' . self::mes($m - 3) . '–' . self::mes($m) . ' ' . $y . ')', 'anio' => $y, 'mes' => $m, 'fecha' => $f];
            }
        }
        return $out;
    }

    /**
     * Próximos vencimientos de varios clientes, ordenados por fecha, con el estado del mes (si ya se marcó como presentado).
     * $atras: días hacia atrás para mostrar los vencidos que no se marcaron como presentados.
     */
    public static function proximos(Collection $clientes, int $dias = 45, int $atras = 0, bool $sinScope = false): array
    {
        $hoy = Carbon::today();
        $desde = $hoy->copy()->subDays($atras);
        $hasta = $hoy->copy()->addDays($dias);
        $lista = [];
        foreach ($clientes as $c) {
            foreach (self::deCliente($c, $desde, $hasta) as $v) {
                $lista[] = $v + ['cliente' => $c];
            }
        }
        if (!$lista) return [];

        // Estado: el 350 se toma del mes declarado; el IVA, del último mes del bimestre o cuatrimestre
        $q = $sinScope ? ContablePeriodo::withoutGlobalScopes() : ContablePeriodo::query();
        $periodos = $q->whereIn('cliente_id', collect($lista)->pluck('cliente.id')->unique())
            ->get(['id', 'cliente_id', 'anio', 'mes', 'estado'])
            ->keyBy(fn ($p) => $p->cliente_id . '-' . $p->anio . '-' . $p->mes);
        foreach ($lista as &$v) {
            $p = $periodos->get($v['cliente']->id . '-' . $v['anio'] . '-' . $v['mes']);
            $v['periodo_id'] = $p?->id;
            $v['presentado'] = $p?->estado === 'presentado';
            $v['dias'] = (int) $hoy->diffInDays($v['fecha'], false);
        }
        unset($v);
        usort($lista, fn ($a, $b) => [$a['fecha'], $a['cliente']->nombre, $a['tipo']] <=> [$b['fecha'], $b['cliente']->nombre, $b['tipo']]);
        return $lista;
    }

    /** Texto corto de cuánto falta */
    public static function falta(int $dias): array
    {
        if ($dias < 0) return ['Venció hace ' . abs($dias) . ($dias === -1 ? ' día' : ' días'), 'danger'];
        if ($dias === 0) return ['Vence hoy', 'danger'];
        if ($dias === 1) return ['Vence mañana', 'warning'];
        if ($dias <= 5) return ['Faltan ' . $dias . ' días', 'warning'];
        return ['Faltan ' . $dias . ' días', 'secondary'];
    }
}
