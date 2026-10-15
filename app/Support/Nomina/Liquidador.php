<?php

namespace App\Support\Nomina;

/**
 * Liquidación de nómina colombiana. Es cálculo puro (sin base de datos) para poder probarlo aparte.
 *
 * Entra: datos del empleado, del periodo, sus novedades y si el negocio está exonerado (art. 114-1 E.T.).
 * Sale: devengos, deducciones, neto, IBC, aportes del empleador y provisiones de prestaciones.
 */
class Liquidador
{
    // Valores legales por año (Decretos 1469 y 1470 de 2025 para 2026)
    public const ANIOS = [
        2025 => ['smmlv' => 1423500, 'aux' => 200000],
        2026 => ['smmlv' => 1750905, 'aux' => 249095],
    ];

    // Tarifa ARL por clase de riesgo (Decreto 1772 de 1994)
    public const ARL = [1 => 0.00522, 2 => 0.01044, 3 => 0.02436, 4 => 0.04350, 5 => 0.06960];

    public const TIPOS_TRABAJADOR = [
        'dependiente' => 'Dependiente (contrato laboral)',
        'aprendiz_lectiva' => 'Aprendiz SENA - etapa lectiva',
        'aprendiz_productiva' => 'Aprendiz SENA - etapa productiva',
    ];

    // Novedades que se pueden registrar por empleado en cada periodo
    public const NOVEDADES = [
        'dias' => [
            'dias_ausencia' => 'Días no laborados / licencia no remunerada',
            'dias_incapacidad' => 'Días de incapacidad general',
            'dias_vacaciones' => 'Días de vacaciones disfrutadas',
            'dias_licencia' => 'Días de licencia remunerada',
            'dias_vac_compensadas' => 'Días de vacaciones compensadas en dinero',
        ],
        'horas' => [
            'hed' => 'Horas extra diurnas',
            'hen' => 'Horas extra nocturnas',
            'rn' => 'Horas con recargo nocturno',
            'hedf' => 'Horas extra diurnas dominicales/festivas',
            'henf' => 'Horas extra nocturnas dominicales/festivas',
            'rdf' => 'Horas ordinarias en domingo/festivo',
            'rndf' => 'Horas nocturnas en domingo/festivo',
        ],
        'devengos' => [
            'comisiones' => 'Comisiones',
            'bonif_salarial' => 'Bonificación salarial',
            'bonif_no_salarial' => 'Bonificación no salarial',
            'aux_no_salarial' => 'Auxilio no salarial (alimentación, etc.)',
            'pago_cesantias' => 'Pago de cesantías',
            'pago_intereses' => 'Pago de intereses a cesantías',
        ],
        'deducciones' => [
            'prestamo' => 'Préstamo / anticipo',
            'libranza' => 'Libranza',
            'embargo' => 'Embargo judicial',
            'retencion' => 'Retención en la fuente',
            'pension_voluntaria' => 'Pensión voluntaria / AFC',
            'otras_deducciones' => 'Otras deducciones',
        ],
    ];

    public static function anio(int $anio): array
    {
        return self::ANIOS[$anio] ?? self::ANIOS[max(array_keys(self::ANIOS))];
    }

    // Horas al mes según la jornada máxima (Ley 2101 de 2021): 44 h hasta 14/07/2026, 42 h desde el 15/07/2026
    public static function horasMes(string $fecha): int
    {
        return match (true) {
            $fecha >= '2026-07-15' => 210,
            $fecha >= '2025-07-15' => 220,
            $fecha >= '2024-07-15' => 230,
            default => 235,
        };
    }

    // Recargo dominical/festivo (Ley 2466 de 2025): 80% desde 01/07/2025, 90% desde 01/07/2026, 100% desde 01/07/2027
    public static function recargoDominical(string $fecha): float
    {
        return match (true) {
            $fecha >= '2027-07-01' => 1.00,
            $fecha >= '2026-07-01' => 0.90,
            $fecha >= '2025-07-01' => 0.80,
            default => 0.75,
        };
    }

    // Días entre dos fechas en meses de 30 días (como se liquida la nómina en Colombia), ambas incluidas
    public static function dias360(string $desde, string $hasta): int
    {
        if ($hasta < $desde) return 0;
        [$y1, $m1, $d1] = array_map('intval', explode('-', $desde));
        [$y2, $m2, $d2] = array_map('intval', explode('-', $hasta));
        $d1 = min($d1, 30);
        $finMes = (int) date('t', mktime(0, 0, 0, $m2, 1, $y2));
        $d2 = $d2 === $finMes ? 30 : min($d2, 30);
        return max(0, ($y2 - $y1) * 360 + ($m2 - $m1) * 30 + ($d2 - $d1) + 1);
    }

    /**
     * @param array $e  salario, integral, tipo, riesgo_arl, fecha_ingreso, fecha_retiro, aux_transporte (bool|null)
     * @param array $p  inicio, fin (Y-m-d)
     * @param array $n  novedades (ver NOVEDADES) + liquidar_prima (bool)
     * @param bool  $exonerado  exonerado de salud 8,5%, SENA e ICBF (art. 114-1 E.T.) para quien gana < 10 SMMLV
     */
    public static function liquidar(array $e, array $p, array $n, bool $exonerado = true): array
    {
        $v = fn ($k) => max(0, (float) ($n[$k] ?? 0));
        $anio = self::anio((int) substr($p['fin'], 0, 4));
        $smmlv = $anio['smmlv'];
        $salario = (float) $e['salario'];
        $integral = !empty($e['integral']);
        $tipo = $e['tipo'] ?? 'dependiente';
        $aprendiz = str_starts_with($tipo, 'aprendiz');
        $diario = $salario / 30;
        // Personalizado: sin mínimos legales automáticos; el negocio decide seguridad social y prestaciones
        $personal = !empty($e['personalizado']);
        $conSS = !$personal || !empty($e['seguridad_social']);
        $conPrest = !$personal || !empty($e['prestaciones']);

        // Días del periodo que el empleado estuvo vinculado
        $desde = max($p['inicio'], ($e['fecha_ingreso'] ?? null) ?: $p['inicio']);
        $hasta = min($p['fin'], ($e['fecha_retiro'] ?? null) ?: $p['fin']);
        $diasPeriodo = self::dias360($desde, $hasta);
        $diasPeriodo = min($diasPeriodo, self::dias360($p['inicio'], $p['fin']));

        $dAus = min($v('dias_ausencia'), $diasPeriodo);
        $dInc = min($v('dias_incapacidad'), $diasPeriodo - $dAus);
        $dVac = min($v('dias_vacaciones'), $diasPeriodo - $dAus - $dInc);
        $dLic = min($v('dias_licencia'), $diasPeriodo - $dAus - $dInc - $dVac);
        $dTrab = max(0, $diasPeriodo - $dAus - $dInc - $dVac - $dLic);

        $dev = [];
        $add = function (string $cod, string $concepto, $cant, float $valor, bool $salarial, bool $prestacional = true) use (&$dev) {
            if (round($valor) == 0) return;
            $dev[] = ['codigo' => $cod, 'concepto' => $concepto, 'cantidad' => $cant, 'valor' => round($valor), 'salarial' => $salarial, 'prestacional' => $prestacional];
        };

        $add('basico', $aprendiz ? 'Apoyo de sostenimiento' : ($integral ? 'Salario integral' : 'Salario básico'), $dTrab, $diario * $dTrab, !$aprendiz);
        $add('vacaciones', 'Vacaciones disfrutadas', $dVac, $diario * $dVac, true, false);
        $add('licencia_rem', 'Licencia remunerada', $dLic, $diario * $dLic, true);
        // Incapacidad general: 2/3 del salario, nunca menos del mínimo diario (art. 227 CST)
        $add('incapacidad', 'Incapacidad general', $dInc, max($diario * 2 / 3, $smmlv / 30) * $dInc, true);

        // Auxilio de transporte: hasta 2 SMMLV, por días efectivamente trabajados
        $tieneAux = $e['aux_transporte'] ?? null;
        $tieneAux = $tieneAux === null ? (!$personal && !$integral && $tipo !== 'aprendiz_lectiva' && $salario <= 2 * $smmlv) : (bool) $tieneAux;
        $aux = $tieneAux ? $anio['aux'] / 30 * $dTrab : 0;
        $add('transporte', 'Auxilio de transporte', $dTrab, $aux, false, true);

        // Horas extra y recargos
        $hora = $salario / self::horasMes($p['fin']);
        $rd = self::recargoDominical($p['fin']);
        $horas = [
            'hed' => ['Horas extra diurnas', 1.25],
            'hen' => ['Horas extra nocturnas', 1.75],
            'rn' => ['Recargo nocturno', 0.35],
            'hedf' => ['Horas extra diurnas dominicales/festivas', 1.25 + $rd],
            'henf' => ['Horas extra nocturnas dominicales/festivas', 1.75 + $rd],
            'rdf' => ['Recargo dominical/festivo', $rd],
            'rndf' => ['Recargo nocturno dominical/festivo', 0.35 + $rd],
        ];
        if (!$aprendiz && !$integral) {
            foreach ($horas as $k => [$txt, $f]) {
                // Las extras pagan la hora completa más el recargo; los recargos solo el porcentaje
                $pct = in_array($k, ['hed', 'hen', 'hedf', 'henf'], true) ? $f - 1 : $f;
                $add($k, $txt . ' (' . round($pct * 100) . '%)', $v($k), $hora * $f * $v($k), true);
            }
        }

        $add('comisiones', 'Comisiones', null, $v('comisiones'), true);
        $add('bonif_salarial', 'Bonificación salarial', null, $v('bonif_salarial'), true);
        $add('bonif_no_salarial', 'Bonificación no salarial', null, $v('bonif_no_salarial'), false, false);
        $add('aux_no_salarial', 'Auxilio no salarial', null, $v('aux_no_salarial'), false, false);

        // Prima de servicios del semestre (se paga en junio y diciembre)
        if (!empty($n['liquidar_prima']) && !$aprendiz && !$integral && $conPrest) {
            $mes = (int) substr($p['fin'], 5, 2);
            $semIni = substr($p['fin'], 0, 4) . ($mes <= 6 ? '-01-01' : '-07-01');
            $dSem = min(180, self::dias360(max($semIni, ($e['fecha_ingreso'] ?? null) ?: $semIni), $hasta));
            $basePrima = $salario + ($tieneAux ? $anio['aux'] : 0);
            $add('prima', 'Prima de servicios', $dSem, $basePrima * $dSem / 360, false, false);
        }
        $add('vac_compensadas', 'Vacaciones compensadas', $v('dias_vac_compensadas'), $diario * $v('dias_vac_compensadas'), false, false);
        $add('cesantias', 'Pago de cesantías', null, $v('pago_cesantias'), false, false);
        $add('intereses', 'Intereses a cesantías', null, $v('pago_intereses'), false, false);

        $totalDev = array_sum(array_column($dev, 'valor'));

        // IBC (base de cotización)
        $salarial = array_sum(array_map(fn ($d) => $d['salarial'] ? $d['valor'] : 0, $dev));
        if ($integral) $salarial *= 0.7;
        $noSalarial = array_sum(array_map(fn ($d) => in_array($d['codigo'], ['bonif_no_salarial', 'aux_no_salarial'], true) ? $d['valor'] : 0, $dev));
        // Ley 1393 de 2010: lo no salarial que pase del 40% del total se suma al IBC
        $exceso = max(0, $noSalarial - 0.4 * ($salarial + $noSalarial));
        $diasCotiza = max(0, $diasPeriodo - $dAus);
        $ibc = $salarial + $exceso;
        if ($diasCotiza > 0 && !$personal) $ibc = max($ibc, $smmlv / 30 * $diasCotiza);
        $ibc = min($ibc, 25 * $smmlv);
        $ibc = round($ibc);
        $ibcMensual = $diasCotiza > 0 ? $ibc * 30 / $diasCotiza : 0;

        // Deducciones del trabajador
        $ded = [];
        $quita = function (string $cod, string $concepto, ?float $pct, float $valor) use (&$ded) {
            if (round($valor) == 0) return;
            $ded[] = ['codigo' => $cod, 'concepto' => $concepto, 'porcentaje' => $pct, 'valor' => round($valor)];
        };
        if (!$aprendiz && $conSS) {
            $quita('salud', 'Salud', 4, $ibc * 0.04);
            $quita('pension', 'Pensión', 4, $ibc * 0.04);
            $fsp = self::tasaFsp($ibcMensual / $smmlv);
            $quita('fsp', 'Fondo de solidaridad pensional', $fsp * 100, $ibc * $fsp);
        }
        foreach (self::NOVEDADES['deducciones'] as $k => $txt) {
            $quita($k, $txt, null, $v($k));
        }
        $totalDed = array_sum(array_column($ded, 'valor'));

        // Aportes del empleador
        $exento = $exonerado && $ibcMensual < 10 * $smmlv;
        $riesgo = (int) ($e['riesgo_arl'] ?? 1);
        $ap = [];
        if (!$conSS) {
            $ap = [];
        } elseif ($tipo === 'aprendiz_lectiva') {
            $ap = ['salud' => $smmlv / 30 * $diasCotiza * 0.125];
        } elseif ($tipo === 'aprendiz_productiva') {
            $baseA = $smmlv / 30 * $diasCotiza;
            $ap = ['salud' => $baseA * 0.125, 'arl' => $baseA * self::ARL[1]];
        } else {
            $ap = [
                'salud' => $exento ? 0 : $ibc * 0.085,
                'pension' => $ibc * 0.12,
                'arl' => $ibc * (self::ARL[$riesgo] ?? self::ARL[1]),
                'caja' => $ibc * 0.04,
                'icbf' => $exento ? 0 : $ibc * 0.03,
                'sena' => $exento ? 0 : $ibc * 0.02,
            ];
        }
        $ap = array_map(fn ($x) => round($x), $ap);

        // Provisiones de prestaciones sociales
        $prov = [];
        if (!$aprendiz && $conPrest) {
            $basePrest = array_sum(array_map(fn ($d) => $d['prestacional'] ? $d['valor'] : 0, $dev));
            $baseVac = $diario * ($dTrab + $dVac);
            if ($integral) {
                $prov = ['vacaciones' => $baseVac * 0.0417];
            } else {
                $ces = $basePrest * 0.0833;
                $prov = ['cesantias' => $ces, 'intereses' => $ces * 0.12, 'prima' => $basePrest * 0.0833, 'vacaciones' => $baseVac * 0.0417];
            }
            $prov = array_map(fn ($x) => round($x), $prov);
        }

        return [
            'dias_periodo' => $diasPeriodo, 'dias_trabajados' => $dTrab,
            'devengos' => $dev, 'deducciones' => $ded,
            'total_devengado' => $totalDev, 'total_deducido' => $totalDed, 'neto' => $totalDev - $totalDed,
            'ibc' => $ibc, 'aportes' => $ap, 'total_aportes' => array_sum($ap),
            'provisiones' => $prov, 'total_provisiones' => array_sum($prov),
            'exonerado' => $exento, 'valor_hora' => round($hora, 2), 'recargo_dominical' => $rd,
            'smmlv' => $smmlv, 'aux_transporte' => $tieneAux, 'personalizado' => $personal,
            'bajo_minimo' => $diasPeriodo > 0 && $salario < $smmlv && !$aprendiz,
        ];
    }

    // Fondo de solidaridad pensional según el IBC en salarios mínimos (Ley 797 de 2003)
    public static function tasaFsp(float $salarios): float
    {
        return match (true) {
            $salarios >= 20 => 0.02,
            $salarios >= 19 => 0.018,
            $salarios >= 18 => 0.016,
            $salarios >= 17 => 0.014,
            $salarios >= 16 => 0.012,
            $salarios >= 4 => 0.01,
            default => 0.0,
        };
    }
}
