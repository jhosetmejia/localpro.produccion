<?php

namespace App\Support\Nomina;

use App\Models\Ajuste;
use App\Models\Empleado;
use App\Models\NominaElectronica;
use App\Models\NominaLiquidacion;
use App\Models\Tienda;
use App\Support\Dian\Facturador;
use App\Support\TiendaActual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Nómina electrónica DIAN (Resolución 000013 de 2021): un documento soporte por empleado y por mes,
 * transmitido dentro de los 10 primeros días hábiles del mes siguiente. Las correcciones van con nota de ajuste.
 * Usa el mismo proveedor tecnológico configurado para la facturación electrónica (o el modo práctica).
 */
class Electronica
{
    // Liquidaciones cerradas del mes (periodos liquidados o pagados cuya fecha final cae en el mes)
    public static function liquidacionesDelMes(string $mes, ?int $empleadoId = null)
    {
        return NominaLiquidacion::with(['periodo', 'empleado'])
            ->whereHas('periodo', fn ($q) => $q->whereIn('estado', ['liquidada', 'pagada'])->where('fecha_fin', 'like', $mes . '-%'))
            ->when($empleadoId, fn ($q) => $q->where('empleado_id', $empleadoId))
            ->get();
    }

    // Documento vigente de un empleado en el mes (el último que no fue eliminado ni rechazado)
    public static function vigente(int $empleadoId, string $mes): ?NominaElectronica
    {
        $ult = NominaElectronica::where('empleado_id', $empleadoId)->where('mes', $mes)->whereNotIn('estado', ['rechazado'])->latest('id')->first();
        return $ult && !($ult->tipo_xml === '103' && $ult->tipo_nota === 2) ? $ult : null;
    }

    /**
     * Crea y envía los documentos del mes para los empleados que aún no lo tienen. Devuelve [creados, omitidos].
     */
    public static function generarMes(string $mes): array
    {
        $porEmpleado = self::liquidacionesDelMes($mes)->groupBy('empleado_id');
        $creados = 0;
        $omitidos = [];
        foreach ($porEmpleado as $empId => $liqs) {
            $e = $liqs->first()->empleado;
            if (!$e->enviar_dian) { $omitidos[] = $e->nombre . ' (no se reporta a la DIAN)'; continue; }
            if (self::vigente($empId, $mes)) continue;
            $doc = self::crear($e, $mes, $liqs, '102');
            self::enviar($doc);
            $creados++;
        }
        return [$creados, $omitidos];
    }

    // Nota de ajuste: 1 = reemplazar con los datos actuales de la nómina, 2 = eliminar el documento
    public static function ajuste(NominaElectronica $ref, int $tipoNota): NominaElectronica
    {
        $liqs = $tipoNota === 1 ? self::liquidacionesDelMes($ref->mes, $ref->empleado_id) : collect();
        $doc = self::crear($ref->empleado, $ref->mes, $liqs, '103', $tipoNota, $ref);
        return self::enviar($doc);
    }

    private static function crear(Empleado $e, string $mes, $liqs, string $tipoXml, ?int $tipoNota = null, ?NominaElectronica $ref = null): NominaElectronica
    {
        return DB::transaction(function () use ($e, $mes, $liqs, $tipoXml, $tipoNota, $ref) {
            $t = Tienda::whereKey(TiendaActual::id())->lockForUpdate()->first();
            $n = (int) $t->consecutivo_ne + 1;
            $t->forceFill(['consecutivo_ne' => $n])->save();
            $pref = $t->nomina_prefijo ?: 'NE';
            $tot = self::totales($liqs);
            return NominaElectronica::create([
                'empleado_id' => $e->id, 'mes' => $mes, 'tipo_xml' => $tipoXml, 'tipo_nota' => $tipoNota, 'referencia_id' => $ref?->id,
                'prefijo' => $pref, 'consecutivo' => $n, 'numero' => $pref . $n, 'estado' => 'pendiente',
                'ambiente' => $t->fe_ambiente ?: 'pruebas',
                'devengado' => $tipoNota === 2 ? 0 : $tot['devengado'], 'deducido' => $tipoNota === 2 ? 0 : $tot['deducido'],
                'total' => $tipoNota === 2 ? 0 : $tot['devengado'] - $tot['deducido'],
            ]);
        });
    }

    private static function totales($liqs): array
    {
        return ['devengado' => (float) $liqs->sum('total_devengado'), 'deducido' => (float) $liqs->sum('total_deducido')];
    }

    // Suma por concepto todas las líneas de las liquidaciones del mes
    private static function sumar($liqs, string $lado): array
    {
        $out = [];
        foreach ($liqs as $l) {
            foreach ($l->resultado[$lado] ?? [] as $x) {
                $k = $x['codigo'];
                $out[$k] ??= ['valor' => 0, 'cantidad' => 0, 'porcentaje' => $x['porcentaje'] ?? null, 'concepto' => $x['concepto']];
                $out[$k]['valor'] += $x['valor'];
                $out[$k]['cantidad'] += (float) ($x['cantidad'] ?? 0);
            }
        }
        return $out;
    }

    /**
     * Documento en el formato de LocalPro (estructura de la DIAN: NominaIndividual / NominaIndividualDeAjuste).
     * El proveedor tecnológico lo convierte a XML UBL, lo firma y lo transmite.
     */
    public static function payload(NominaElectronica $d): array
    {
        $t = TiendaActual::tienda();
        $e = $d->empleado;
        $liqs = $d->tipo_nota === 2 ? collect() : self::liquidacionesDelMes($d->mes, $e->id);
        $dev = self::sumar($liqs, 'devengos');
        $ded = self::sumar($liqs, 'deducciones');
        $periodos = $liqs->pluck('periodo')->sortBy('fecha_inicio');
        $ini = $periodos->first()?->fecha_inicio?->format('Y-m-d') ?? $d->mes . '-01';
        $fin = $periodos->last()?->fecha_fin?->format('Y-m-d') ?? date('Y-m-t', strtotime($d->mes . '-01'));
        $fecha = ($d->created_at ?? now())->format('Y-m-d');
        $hora = ($d->created_at ?? now())->format('H:i:s') . '-05:00';
        $g = fn ($k) => round($dev[$k]['valor'] ?? 0, 2);
        $c = fn ($k) => $dev[$k]['cantidad'] ?? 0;

        $horas = [];
        foreach (['hed' => ['HED', 25], 'hen' => ['HEN', 75], 'rn' => ['HRN', 35], 'hedf' => ['HEDDF', null], 'henf' => ['HENDF', null], 'rdf' => ['HRDDF', null], 'rndf' => ['HRNDF', null]] as $k => [$tag, $pct]) {
            if (!empty($dev[$k])) {
                preg_match('/\((\d+)%\)/', $dev[$k]['concepto'], $m);
                $horas[$tag] = ['Cantidad' => $c($k), 'Porcentaje' => $pct ?? (int) ($m[1] ?? 0), 'Pago' => $g($k)];
            }
        }

        $devengados = array_filter([
            'Basico' => ['DiasTrabajados' => (int) $c('basico'), 'SueldoTrabajado' => $g('basico')],
            'Transporte' => $g('transporte') ? ['AuxilioTransporte' => $g('transporte')] : null,
            'HorasExtrasRecargos' => $horas ?: null,
            'Vacaciones' => ($g('vacaciones') || $g('vac_compensadas')) ? array_filter([
                'VacacionesComunes' => $g('vacaciones') ? ['Cantidad' => (int) $c('vacaciones'), 'Pago' => $g('vacaciones')] : null,
                'VacacionesCompensadas' => $g('vac_compensadas') ? ['Cantidad' => (int) $c('vac_compensadas'), 'Pago' => $g('vac_compensadas')] : null,
            ]) : null,
            'Primas' => $g('prima') ? ['Cantidad' => (int) $c('prima'), 'Pago' => $g('prima')] : null,
            'Cesantias' => ($g('cesantias') || $g('intereses')) ? ['Pago' => $g('cesantias'), 'Porcentaje' => 12, 'PagoIntereses' => $g('intereses')] : null,
            'Incapacidades' => $g('incapacidad') ? [['Cantidad' => (int) $c('incapacidad'), 'Tipo' => 1, 'Pago' => $g('incapacidad')]] : null,
            'Licencias' => $g('licencia_rem') ? ['LicenciaR' => [['Cantidad' => (int) $c('licencia_rem'), 'Pago' => $g('licencia_rem')]]] : null,
            'Bonificaciones' => ($g('bonif_salarial') || $g('bonif_no_salarial')) ? ['BonificacionS' => $g('bonif_salarial'), 'BonificacionNS' => $g('bonif_no_salarial')] : null,
            'Auxilios' => $g('aux_no_salarial') ? ['AuxilioNS' => $g('aux_no_salarial')] : null,
            'Comisiones' => $g('comisiones') ?: null,
        ]);

        $q = fn ($k) => round($ded[$k]['valor'] ?? 0, 2);
        $deducciones = array_filter([
            'Salud' => $q('salud') ? ['Porcentaje' => 4, 'Deduccion' => $q('salud')] : null,
            'FondoPension' => $q('pension') ? ['Porcentaje' => 4, 'Deduccion' => $q('pension')] : null,
            'FondoSP' => $q('fsp') ? ['Porcentaje' => $ded['fsp']['porcentaje'] ?? 1, 'DeduccionSP' => $q('fsp')] : null,
            'Libranzas' => $q('libranza') ? [['Descripcion' => 'Libranza', 'Deduccion' => $q('libranza')]] : null,
            'Anticipos' => $q('prestamo') ?: null,
            'PensionVoluntaria' => $q('pension_voluntaria') ?: null,
            'RetencionFuente' => $q('retencion') ?: null,
            'OtrasDeducciones' => ($q('embargo') + $q('otras_deducciones')) ?: null,
        ]);

        $tipoDoc = ['13' => '13', '22' => '22', '41' => '41', '47' => '47', '48' => '48', '12' => '12'][$e->tipo_documento] ?? '13';
        $p = [
            'tipo_xml' => $d->tipo_xml, 'tipo_nota' => $d->tipo_nota,
            'ambiente' => $d->ambiente === 'produccion' ? '1' : '2',
            'numero' => $d->numero, 'prefijo' => $d->prefijo, 'consecutivo' => $d->consecutivo,
            'fecha' => $fecha, 'hora' => $hora, 'mes' => $d->mes,
            'predecesor' => $d->referencia ? ['numero' => $d->referencia->numero, 'cune' => $d->referencia->cune, 'fecha' => $d->referencia->created_at?->format('Y-m-d')] : null,
            'periodo' => [
                'FechaIngreso' => $e->fecha_ingreso?->format('Y-m-d'), 'FechaRetiro' => $e->fecha_retiro?->format('Y-m-d'),
                'FechaLiquidacionInicio' => $ini, 'FechaLiquidacionFin' => $fin,
                'TiempoLaborado' => \App\Support\Nomina\Liquidador::dias360($e->fecha_ingreso?->format('Y-m-d') ?? $ini, $fin),
                'FechaGen' => $fecha,
            ],
            'periodo_nomina' => ($t->nomina_periodicidad ?? 'mensual') === 'quincenal' ? '4' : '5',
            'moneda' => 'COP',
            'empleador' => [
                'RazonSocial' => $t->razon_social ?: $t->nombre, 'NIT' => $t->nit, 'DV' => $t->dv,
                'Pais' => 'CO', 'MunicipioCiudad' => $t->municipio_dane, 'Direccion' => $t->direccion,
            ],
            'trabajador' => [
                'TipoTrabajador' => match ($e->tipo) { 'aprendiz_lectiva' => '12', 'aprendiz_productiva' => '19', default => '01' },
                'SubTipoTrabajador' => '00', 'AltoRiesgoPension' => false,
                'TipoDocumento' => $tipoDoc, 'NumeroDocumento' => $e->documento,
                'PrimerApellido' => explode(' ', trim($e->apellidos))[0] ?? '', 'SegundoApellido' => trim(implode(' ', array_slice(explode(' ', trim($e->apellidos)), 1))) ?: null,
                'PrimerNombre' => explode(' ', trim($e->nombres))[0] ?? '', 'OtrosNombres' => trim(implode(' ', array_slice(explode(' ', trim($e->nombres)), 1))) ?: null,
                'LugarTrabajoPais' => 'CO', 'LugarTrabajoMunicipioCiudad' => $e->municipio_dane ?: $t->municipio_dane,
                'LugarTrabajoDireccion' => $e->direccion ?: $t->direccion,
                'SalarioIntegral' => $e->salario_integral, 'TipoContrato' => Empleado::CONTRATOS_DIAN[$e->tipo_contrato] ?? '2',
                'Sueldo' => round($e->salario, 2), 'CodigoTrabajador' => (string) $e->id,
            ],
            'pago' => [
                'Forma' => '1', 'Metodo' => match ($e->forma_pago) { 'efectivo' => '10', 'cheque' => '20', default => '47' },
                'Banco' => $e->banco, 'TipoCuenta' => $e->tipo_cuenta, 'NumeroCuenta' => $e->numero_cuenta,
            ],
            'fechas_pagos' => $periodos->map(fn ($p) => $p->fecha_pago?->format('Y-m-d'))->filter()->values()->all() ?: [$fin],
            'devengados' => $d->tipo_nota === 2 ? [] : $devengados,
            'deducciones' => $d->tipo_nota === 2 ? [] : $deducciones,
            'totales' => ['devengados' => round($d->devengado, 2), 'deducciones' => round($d->deducido, 2), 'comprobante' => round($d->total, 2)],
        ];
        return $p;
    }

    // Lo que la DIAN exige y suele faltar
    public static function errores(array $p): array
    {
        $err = [];
        $em = $p['empleador'];
        if (blank($em['NIT'])) $err[] = 'Falta el NIT del negocio (Facturación DIAN → datos fiscales).';
        if (blank($em['MunicipioCiudad'])) $err[] = 'Falta el código DANE del municipio del negocio.';
        if (blank($em['Direccion'])) $err[] = 'Falta la dirección del negocio.';
        if ($p['tipo_nota'] === 2) return $err;
        $tr = $p['trabajador'];
        if (blank($tr['NumeroDocumento'])) $err[] = 'Falta el documento del empleado.';
        if (blank($tr['PrimerNombre']) || blank($tr['PrimerApellido'])) $err[] = 'Falta el nombre o el apellido del empleado.';
        if (blank($tr['LugarTrabajoMunicipioCiudad'])) $err[] = 'Falta el municipio (DANE) donde trabaja el empleado.';
        if ($p['totales']['devengados'] <= 0) $err[] = 'El empleado no tiene pagos en el mes.';
        return $err;
    }

    // CUNE: SHA-384 de los datos del documento (Anexo técnico de nómina electrónica)
    public static function cune(array $p, string $pin): string
    {
        $f = fn ($v) => number_format((float) $v, 2, '.', '');
        return hash('sha384',
            $p['numero'] . $p['fecha'] . $p['hora'] . $f($p['totales']['devengados']) . $f($p['totales']['deducciones'])
            . $f($p['totales']['comprobante']) . $p['empleador']['NIT'] . ($p['trabajador']['NumeroDocumento'] ?? '0')
            . $p['tipo_xml'] . $pin . $p['ambiente']);
    }

    public static function enviar(NominaElectronica $d): NominaElectronica
    {
        $antes = TiendaActual::id();
        TiendaActual::set($d->tienda_id);
        try {
            $p = self::payload($d->fresh(['empleado', 'referencia']));
            $prov = Ajuste::valor('fe_proveedor');
            $base = ['payload' => $p, 'intentos' => $d->intentos + 1, 'enviado_en' => now()];
            if ($prov === 'simulado') {
                $err = self::errores($p);
                $d->forceFill($base + ($err
                    ? ['estado' => 'rechazado', 'proveedor' => 'simulado', 'ultimo_error' => implode(' · ', $err), 'respuesta' => ['errores' => $err]]
                    : ['estado' => 'simulado', 'proveedor' => 'simulado', 'cune' => self::cune($p, 'SIMULADO'), 'ultimo_error' => null, 'validado_en' => now(),
                        'respuesta' => ['simulado' => true, 'mensaje' => 'Prueba interna correcta: el documento cumple la estructura. No fue enviado a la DIAN.']]))->save();
            } elseif ($prov === 'api' && ($url = Ajuste::valor('fe_url')) && ($token = Facturador::secreto('fe_token'))) {
                $r = Http::timeout(40)->acceptJson()->withToken($token)
                    ->post(rtrim($url, '/') . '/nominas', ['credenciales' => TiendaActual::tienda()?->fe_credenciales ?? [], 'documento' => $p]);
                $j = $r->json() ?? [];
                $estado = !$r->successful() ? ($r->status() >= 500 ? 'error' : 'rechazado') : (in_array($j['estado'] ?? '', ['aceptado', 'enviado', 'rechazado'], true) ? $j['estado'] : 'enviado');
                $d->forceFill($base + [
                    'estado' => $estado, 'proveedor' => Ajuste::valor('fe_nombre') ?: 'api', 'respuesta' => $j,
                    'cune' => $j['cune'] ?? $d->cune,
                    'ultimo_error' => in_array($estado, ['rechazado', 'error'], true) ? mb_substr((string) ($j['mensaje'] ?? $j['message'] ?? 'El proveedor respondió ' . $r->status()), 0, 1000) : null,
                    'validado_en' => $estado === 'aceptado' ? now() : $d->validado_en,
                ])->save();
            } else {
                $d->forceFill(['payload' => $p, 'ultimo_error' => 'Sin proveedor tecnológico conectado: queda en cola.'])->save();
            }
        } catch (Throwable $ex) {
            Log::error('Nómina electrónica ' . $d->id . ': ' . $ex->getMessage());
            $d->forceFill(['estado' => 'error', 'ultimo_error' => mb_substr($ex->getMessage(), 0, 1000), 'intentos' => $d->intentos + 1])->save();
        } finally {
            TiendaActual::set($antes);
        }
        return $d->refresh();
    }
}
