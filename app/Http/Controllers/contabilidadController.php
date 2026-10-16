<?php

namespace App\Http\Controllers;

use App\Models\ContableCliente;
use App\Models\ContableDocumento;
use App\Models\ContablePeriodo;
use App\Models\ContableTarifa;
use App\Models\ContableTercero;
use App\Support\CalendarioTributario;
use App\Support\EspacioContable;
use App\Support\HojaCalculo;
use App\Support\Retenciones;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Contabilidad: retenciones e IVA para analistas contables.
 * El analista registra sus clientes; por cada mes sube el listado de facturas de la DIAN y la base de su programa
 * contable, y el sistema cruza, calcula la retención que debió practicarse y arma el borrador del 350 y del IVA.
 * La declaración se presenta en el portal de la DIAN con la firma del declarante: aquí se deja lista para pasarla.
 */
class contabilidadController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            // El asesor de la red tiene su propio espacio contable desde su panel
            abort_unless($u && ($u->es_superadmin || EspacioContable::esAsesor() || $u->can('contabilidad') || $u->can('editar-negocio')), 403);
            if (!EspacioContable::activo() && !$request->routeIs('contabilidad.index', 'contabilidad.activar', 'contabilidad.guia')) {
                return redirect()->route('contabilidad.index');
            }
            return $next($request);
        });
    }

    private function mio($m)
    {
        abort_unless(EspacioContable::esMio($m), 404);
        return $m;
    }

    private function carpeta(): string
    {
        return EspacioContable::carpeta();
    }

    // ===================== Calendario tributario =====================

    public function calendario(Request $r)
    {
        $clientes = ContableCliente::where('activo', true)->orderBy('nombre')->get();
        $dias = in_array((int) $r->query('dias'), [15, 30, 60, 90], true) ? (int) $r->query('dias') : 60;
        $lista = CalendarioTributario::proximos($clientes, $dias, 15);
        $porFecha = collect($lista)->groupBy(fn ($v) => $v['fecha']->toDateString());
        return view('contabilidad.calendario', [
            'porFecha' => $porFecha, 'clientes' => $clientes, 'dias' => $dias,
            'pendientes' => count(array_filter($lista, fn ($v) => !$v['presentado'] && $v['dias'] <= 5)),
            'ultimoMes' => CalendarioTributario::ultimoMes(),
        ]);
    }

    // ===================== Datos de ejemplo =====================

    // Crea un cliente y un mes de ejemplo con los archivos de muestra, para ver el cruce sin subir nada propio
    public function ejemplo()
    {
        $nit = '900123456';
        $c = ContableCliente::firstOrCreate(['nit' => $nit], [
            'nombre' => 'EMPRESA DE EJEMPLO SAS', 'dv' => '7', 'tipo_persona' => 'juridica', 'agente_retenedor' => true,
            'autorretenedor' => false, 'agente_rete_iva' => false, 'periodicidad_iva' => 'bimestral', 'ciudad' => 'Ejemplo',
            'notas' => 'Cliente de ejemplo creado por LocalProo para conocer la herramienta. Puede eliminarlo cuando quiera.',
            'activo' => true, 'recordatorios' => false,
        ]);
        $p = ContablePeriodo::firstOrCreate(['cliente_id' => $c->id, 'anio' => 2026, 'mes' => 9]);

        $carpeta = $this->carpeta() . '/' . $c->id;
        $rutaDian = $carpeta . '/2026-9-dian.xlsx';
        $rutaCont = $carpeta . '/2026-9-contable.xlsx';
        Storage::disk('local')->put($rutaDian, file_get_contents(resource_path('contabilidad/ejemplo-dian.xlsx')));
        Storage::disk('local')->put($rutaCont, file_get_contents(resource_path('contabilidad/ejemplo-contable.xlsx')));

        $docsDian = Retenciones::leerDian(Storage::disk('local')->path($rutaDian), 'xlsx', $c->nit);
        $vista = Retenciones::vistaContable(Storage::disk('local')->path($rutaCont), 'xlsx');
        $docsCont = Retenciones::leerContable(Storage::disk('local')->path($rutaCont), 'xlsx', $vista['mapa'], $vista['fila']);

        DB::transaction(function () use ($p, $docsDian, $docsCont, $rutaDian, $rutaCont, $vista) {
            ContableDocumento::where('periodo_id', $p->id)->delete();
            foreach ($docsDian as $d) ContableDocumento::create($d + ['periodo_id' => $p->id]);
            foreach ($docsCont as $d) ContableDocumento::create($d + ['periodo_id' => $p->id]);
            $p->update(['archivo_dian' => $rutaDian, 'archivo_contable' => $rutaCont, 'estado' => 'abierto',
                'mapeo_contable' => ['titulos' => $vista['titulos'], 'mapa' => $vista['mapa']]]);
        });
        Retenciones::cruzar($p);

        return redirect()->route('contabilidad.periodo', $p)->with('success',
            'Este es un cliente de ejemplo con los archivos ya cargados: revise el cruce, el borrador del 350 y el IVA. Pruebe cambiar el concepto de ASEO TOTAL a "Servicios generales" y vea cómo cambia la retención.');
    }

    // Archivos de muestra para ver el formato esperado
    public function ejemploArchivo(string $archivo)
    {
        return response()->download(resource_path('contabilidad/ejemplo-' . $archivo . '.xlsx'),
            $archivo === 'dian' ? 'ejemplo-listado-facturas-DIAN.xlsx' : 'ejemplo-auxiliar-contable.xlsx');
    }

    // Guía de uso con las normas y fuentes
    public function guia()
    {
        return view('contabilidad.guia', ['uvt' => Retenciones::UVT]);
    }

    // ===================== Clientes =====================

    public function index()
    {
        $tienda = TiendaActual::tienda();
        if (!EspacioContable::activo()) return view('contabilidad.activar', ['tienda' => $tienda]);
        Retenciones::asegurarTarifas();
        $clientes = ContableCliente::withCount('periodos')->orderByDesc('activo')->orderBy('nombre')->get();
        $ultimos = ContablePeriodo::with('cliente')->latest('updated_at')->take(8)->get();
        $vencimientos = array_slice(array_values(array_filter(
            CalendarioTributario::proximos($clientes->where('activo', true), 30, 10),
            fn ($v) => !$v['presentado']
        )), 0, 6);
        return view('contabilidad.index', compact('tienda', 'clientes', 'ultimos', 'vencimientos'));
    }

    public function activar()
    {
        abort_unless((auth()->user()->can('editar-negocio') || auth()->user()->es_superadmin) && TiendaActual::tienda(), 403);
        TiendaActual::tienda()->forceFill(['mod_contable' => true])->save();
        return redirect()->route('contabilidad.index')->with('success', 'Contabilidad activada. Registre su primer cliente.');
    }

    private function datosCliente(Request $r): array
    {
        $d = $r->validate([
            'nombre' => 'required|string|max:150',
            'nit' => 'required|string|max:20',
            'dv' => 'nullable|string|max:2',
            'tipo_persona' => ['required', Rule::in(['juridica', 'natural'])],
            'periodicidad_iva' => ['required', Rule::in(array_keys(ContableCliente::PERIODICIDAD))],
            'ciudad' => 'nullable|string|max:80',
            'notas' => 'nullable|string|max:2000',
        ], ['nombre.required' => 'Escriba el nombre o razón social.', 'nit.required' => 'Escriba el NIT o la cédula.']);
        $d['nit'] = Retenciones::nit($d['nit']);
        $d['agente_retenedor'] = $r->boolean('agente_retenedor');
        $d['autorretenedor'] = $r->boolean('autorretenedor');
        $d['agente_rete_iva'] = $r->boolean('agente_rete_iva');
        $d['recordatorios'] = $r->boolean('recordatorios', true);
        $d['activo'] = $r->boolean('activo', true);
        return $d;
    }

    public function clienteStore(Request $r)
    {
        $c = ContableCliente::create($this->datosCliente($r));
        return redirect()->route('contabilidad.cliente', $c)->with('success', 'Cliente creado. Abra el mes que va a revisar.');
    }

    public function clienteUpdate(Request $r, ContableCliente $cliente)
    {
        $this->mio($cliente)->update($this->datosCliente($r));
        return back()->with('success', 'Datos del cliente guardados.');
    }

    public function clienteDestroy(ContableCliente $cliente)
    {
        $this->mio($cliente);
        Storage::disk('local')->deleteDirectory($this->carpeta() . '/' . $cliente->id);
        $cliente->delete();
        return redirect()->route('contabilidad.index')->with('success', 'Cliente eliminado con todos sus meses.');
    }

    public function cliente(ContableCliente $cliente)
    {
        $this->mio($cliente);
        $periodos = $cliente->periodos()->withCount('documentos')->orderByDesc('anio')->orderByDesc('mes')->get();
        $terceros = $cliente->terceros()->orderBy('nombre')->get();
        $vencimientos = CalendarioTributario::proximos(collect([$cliente]), 75, 10);
        return view('contabilidad.cliente', compact('cliente', 'periodos', 'terceros', 'vencimientos'));
    }

    public function periodoStore(Request $r, ContableCliente $cliente)
    {
        $this->mio($cliente);
        $d = $r->validate(['anio' => 'required|integer|min:2020|max:2100', 'mes' => 'required|integer|min:1|max:12']);
        $p = ContablePeriodo::firstOrCreate(['cliente_id' => $cliente->id, 'anio' => $d['anio'], 'mes' => $d['mes']]);
        return redirect()->route('contabilidad.periodo', $p);
    }

    // ===================== Periodo (el mes) =====================

    public function periodo(Request $r, ContablePeriodo $periodo)
    {
        $this->mio($periodo)->load('cliente');
        $filas = Retenciones::analisis($periodo);
        $ver = $r->query('ver', 'todas');
        $filtradas = array_values(array_filter($filas, function ($f) use ($ver) {
            return match ($ver) {
                'compras' => $f['direccion'] === 'recibida',
                'ventas' => $f['direccion'] === 'emitida',
                'revisar' => !empty($f['obs']),
                'sin_contabilizar' => (bool) array_filter($f['obs'], fn ($o) => $o[0] === 'solo_dian'),
                'retencion' => (bool) array_filter($f['obs'], fn ($o) => in_array($o[0], ['falta_retencion', 'sobra_retencion', 'falta_rete_iva', 'sobra_rete_iva'], true)),
                default => true,
            };
        }));
        $conteo = [
            'dian' => ContableDocumento::where('periodo_id', $periodo->id)->where('origen', 'dian')->count(),
            'contable' => ContableDocumento::where('periodo_id', $periodo->id)->where('origen', 'contable')->count(),
            'revisar' => count(array_filter($filas, fn ($f) => !empty($f['obs']))),
            'sin_contabilizar' => count(array_filter($filas, fn ($f) => (bool) array_filter($f['obs'], fn ($o) => $o[0] === 'solo_dian'))),
            'retencion' => count(array_filter($filas, fn ($f) => (bool) array_filter($f['obs'], fn ($o) => in_array($o[0], ['falta_retencion', 'sobra_retencion', 'falta_rete_iva', 'sobra_rete_iva'], true)))),
        ];

        // IVA del periodo de IVA completo (bimestre o cuatrimestre) al que pertenece este mes
        $meses = Retenciones::mesesIva($periodo->cliente->periodicidad_iva, $periodo->mes);
        $ivaMeses = [];
        foreach ($meses as $m) {
            $pm = $m === $periodo->mes ? $periodo : ContablePeriodo::where('cliente_id', $periodo->cliente_id)->where('anio', $periodo->anio)->where('mes', $m)->first();
            $ivaMeses[$m] = $pm ? Retenciones::iva($pm->id === $periodo->id ? $filas : Retenciones::analisis($pm)) : null;
        }

        return view('contabilidad.periodo', [
            'p' => $periodo, 'cliente' => $periodo->cliente, 'filas' => $filtradas, 'todas' => $filas, 'ver' => $ver, 'conteo' => $conteo,
            'b350' => Retenciones::borrador350($periodo, $filas), 'iva' => Retenciones::iva($filas), 'ivaMeses' => $ivaMeses,
            'uvt' => Retenciones::uvt($periodo->anio), 'tarifas' => ContableTarifa::orderBy('orden')->get(),
        ]);
    }

    private function archivo(Request $r, string $campo): array
    {
        $r->validate([$campo => 'required|file|max:20480|mimes:xlsx,xlsm,csv,txt'], [
            $campo . '.required' => 'Elija el archivo.', $campo . '.mimes' => 'Suba el archivo en Excel (.xlsx) o CSV.',
        ]);
        $f = $r->file($campo);
        return [$f, strtolower($f->getClientOriginalExtension() ?: 'xlsx')];
    }

    public function subirDian(Request $r, ContablePeriodo $periodo)
    {
        $this->mio($periodo)->load('cliente');
        [$f, $ext] = $this->archivo($r, 'archivo');
        try {
            $docs = Retenciones::leerDian($f->getRealPath(), $ext, $periodo->cliente->nit);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['archivo' => $e->getMessage()]);
        }
        $ruta = $f->storeAs($this->carpeta() . '/' . $periodo->cliente_id, $periodo->anio . '-' . $periodo->mes . '-dian.' . $ext, 'local');
        DB::transaction(function () use ($periodo, $docs, $ruta) {
            ContableDocumento::where('periodo_id', $periodo->id)->where('origen', 'dian')->delete();
            // El concepto que ya se había elegido en la contabilidad se conserva al recargar
            foreach ($docs as $d) ContableDocumento::create($d + ['periodo_id' => $periodo->id]);
            $periodo->update(['archivo_dian' => $ruta]);
        });
        Retenciones::cruzar($periodo);
        $fuera = collect($docs)->filter(fn ($d) => $d['fecha'] && substr($d['fecha'], 0, 7) !== sprintf('%04d-%02d', $periodo->anio, $periodo->mes))->count();
        return redirect()->route('contabilidad.periodo', $periodo)->with('success', count($docs) . ' documentos de la DIAN cargados.' . ($fuera ? " Ojo: $fuera son de otro mes." : ''));
    }

    public function subirContable(Request $r, ContablePeriodo $periodo)
    {
        $this->mio($periodo);
        [$f, $ext] = $this->archivo($r, 'archivo');
        $ruta = $f->storeAs($this->carpeta() . '/' . $periodo->cliente_id, $periodo->anio . '-' . $periodo->mes . '-contable.' . $ext, 'local');
        $periodo->update(['archivo_contable' => $ruta]);
        return redirect()->route('contabilidad.mapeo', $periodo);
    }

    // Paso para decir qué columna del archivo contable es cada dato
    public function mapeo(ContablePeriodo $periodo)
    {
        $this->mio($periodo)->load('cliente');
        abort_unless($periodo->archivo_contable && Storage::disk('local')->exists($periodo->archivo_contable), 404);
        $ruta = Storage::disk('local')->path($periodo->archivo_contable);
        try {
            $vista = Retenciones::vistaContable($ruta, pathinfo($ruta, PATHINFO_EXTENSION));
        } catch (\RuntimeException $e) {
            return redirect()->route('contabilidad.periodo', $periodo)->withErrors(['archivo' => $e->getMessage()]);
        }
        // Si ya se mapeó antes un archivo con los mismos títulos (mismo programa contable), se usa ese mapeo
        $anterior = ContablePeriodo::where('cliente_id', $periodo->cliente_id)->whereNotNull('mapeo_contable')->latest('updated_at')->value('mapeo_contable');
        if (is_string($anterior)) $anterior = json_decode($anterior, true);
        if ($anterior && ($anterior['titulos'] ?? null) === $vista['titulos']) $vista['mapa'] = $anterior['mapa'];
        return view('contabilidad.mapeo', ['p' => $periodo, 'v' => $vista]);
    }

    public function mapeoGuardar(Request $r, ContablePeriodo $periodo)
    {
        $this->mio($periodo);
        $ruta = Storage::disk('local')->path($periodo->archivo_contable);
        $vista = Retenciones::vistaContable($ruta, pathinfo($ruta, PATHINFO_EXTENSION));
        $mapa = array_filter((array) $r->input('mapa', []), fn ($v) => $v !== null && $v !== '');
        $mapa = array_map('intval', array_intersect_key($mapa, Retenciones::CAMPOS_CONTABLES));
        if (!isset($mapa['nit'], $mapa['documento'])) {
            return back()->withErrors(['mapa' => 'Indique al menos la columna del NIT del tercero y la del número de factura.']);
        }
        $fila = (int) $r->input('fila', $vista['fila']);
        $docs = Retenciones::leerContable($ruta, pathinfo($ruta, PATHINFO_EXTENSION), $mapa, $fila);
        if (!$docs) return back()->withErrors(['mapa' => 'No se encontraron facturas con esas columnas. Revise que el NIT y el número de factura estén bien elegidos.']);
        DB::transaction(function () use ($periodo, $docs, $mapa, $vista) {
            ContableDocumento::where('periodo_id', $periodo->id)->where('origen', 'contable')->delete();
            foreach ($docs as $d) ContableDocumento::create($d + ['periodo_id' => $periodo->id]);
            $periodo->update(['mapeo_contable' => ['titulos' => $vista['titulos'], 'mapa' => $mapa]]);
        });
        Retenciones::cruzar($periodo);
        return redirect()->route('contabilidad.periodo', $periodo)->with('success', count($docs) . ' facturas de la contabilidad cargadas y cruzadas con la DIAN.');
    }

    public function borrarCarga(ContablePeriodo $periodo, string $origen)
    {
        $this->mio($periodo);
        abort_unless(in_array($origen, ['dian', 'contable'], true), 404);
        ContableDocumento::where('periodo_id', $periodo->id)->where('origen', $origen)->delete();
        $campo = $origen === 'dian' ? 'archivo_dian' : 'archivo_contable';
        if ($periodo->$campo) Storage::disk('local')->delete($periodo->$campo);
        $periodo->update([$campo => null]);
        Retenciones::cruzar($periodo);
        return back()->with('success', $origen === 'dian' ? 'Se quitó el listado de la DIAN.' : 'Se quitó la base contable.');
    }

    // Cambia el concepto de retención de una o varias facturas (y opcionalmente lo recuerda para el proveedor)
    public function concepto(Request $r, ContablePeriodo $periodo)
    {
        $this->mio($periodo);
        $d = $r->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'concepto' => ['required', Rule::in(array_keys(Retenciones::CONCEPTOS))]]);
        $docs = ContableDocumento::where('periodo_id', $periodo->id)->whereIn('id', $d['ids'])->get();
        $ids = $docs->pluck('id')->merge($docs->pluck('par_id')->filter())->unique();
        ContableDocumento::where('periodo_id', $periodo->id)->whereIn('id', $ids)->update(['concepto' => $d['concepto']]);
        if ($r->boolean('recordar')) {
            ContableTercero::where('cliente_id', $periodo->cliente_id)->whereIn('nit', $docs->pluck('nit_tercero')->unique())->update(['concepto' => $d['concepto']]);
        }
        return back()->with('success', 'Concepto actualizado en ' . $docs->count() . ' factura(s).');
    }

    public function tercero(Request $r, ContableTercero $tercero)
    {
        $this->mio($tercero);
        $d = $r->validate([
            'tipo_persona' => ['required', Rule::in(['juridica', 'natural'])],
            'concepto' => ['nullable', Rule::in(array_keys(Retenciones::CONCEPTOS))],
        ]);
        $tercero->update($d + ['declarante' => $r->boolean('declarante'), 'autorretenedor' => $r->boolean('autorretenedor'), 'gran_contribuyente' => $r->boolean('gran_contribuyente')]);
        return back()->with('success', 'Datos de ' . ($tercero->nombre ?: $tercero->nit) . ' guardados. Los cálculos ya se actualizaron.');
    }

    public function periodoDatos(Request $r, ContablePeriodo $periodo)
    {
        $this->mio($periodo);
        $d = $r->validate([
            'estado' => ['required', Rule::in(array_keys(ContablePeriodo::ESTADOS))],
            'autorretencion_base' => 'nullable|numeric|min:0',
            'autorretencion_valor' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:3000',
        ]);
        $periodo->update(['estado' => $d['estado'], 'autorretencion_base' => (float) ($d['autorretencion_base'] ?? 0), 'autorretencion_valor' => (float) ($d['autorretencion_valor'] ?? 0), 'notas' => $d['notas'] ?? null]);
        return back()->with('success', 'Guardado.');
    }

    public function periodoDestroy(ContablePeriodo $periodo)
    {
        $this->mio($periodo);
        foreach (['archivo_dian', 'archivo_contable'] as $c) if ($periodo->$c) Storage::disk('local')->delete($periodo->$c);
        $cliente = $periodo->cliente_id;
        $periodo->delete();
        return redirect()->route('contabilidad.cliente', $cliente)->with('success', 'Mes eliminado.');
    }

    // ===================== Exportar =====================

    private function n($v): string
    {
        return number_format((float) $v, 0, ',', '.');
    }

    public function exportar(ContablePeriodo $periodo)
    {
        $this->mio($periodo)->load('cliente');
        $filas = Retenciones::analisis($periodo);
        $con = Retenciones::CONCEPTOS;
        $hoja = [['Tipo', 'Documento', 'Fecha', 'NIT tercero', 'Tercero', 'Persona', 'Concepto', 'Base', 'IVA', 'IVA DIAN', 'IVA contable',
            'Retención practicada', 'Retención según tabla', 'Tarifa', 'Diferencia', 'Rete IVA', 'Rete ICA', 'Revisar']];
        foreach ($filas as $f) {
            $hoja[] = [$f['direccion'] === 'emitida' ? 'Venta' : 'Compra/gasto', $f['documento'], $f['fecha']?->format('d/m/Y'), $f['nit'], $f['nombre'],
                $f['tipo_persona'] === 'juridica' ? 'Jurídica' : 'Natural', $con[$f['concepto']][0] ?? '', round($f['base']), round($f['iva']),
                $f['iva_dian'] !== null ? round($f['iva_dian']) : '', $f['iva_cont'] !== null ? round($f['iva_cont']) : '',
                round($f['practicada']), round($f['esperada']), $f['tarifa'], round($f['esperada'] - $f['practicada']),
                round($f['rete_iva']), round($f['rete_ica']), implode(' · ', array_column($f['obs'], 1))];
        }
        $b = Retenciones::borrador350($periodo, $filas);
        $hoja[] = [];
        $hoja[] = ['BORRADOR RETENCIONES (350) — ' . $periodo->nombre, '', '', '', 'Base jurídicas', 'Retención jurídicas', 'Base naturales', 'Retención naturales'];
        foreach ($b['todos'] as $x) $hoja[] = [$x['nombre'], '', '', '', round($x['base_j']), round($x['ret_j']), round($x['base_n']), round($x['ret_n'])];
        $hoja[] = ['Autorretenciones', '', '', '', round($b['autorretencion_base']), round($b['autorretencion'])];
        $hoja[] = ['Retención de IVA', '', '', '', '', round($b['rete_iva'])];
        $hoja[] = ['TOTAL RETENCIONES', '', '', '', '', round($b['total'])];
        $hoja[] = ['Rete ICA (declaración del municipio)', '', '', '', '', round($b['rete_ica'])];
        $nombre = 'retenciones-' . \Illuminate\Support\Str::slug($periodo->cliente->nombre) . '-' . $periodo->anio . '-' . str_pad($periodo->mes, 2, '0', STR_PAD_LEFT) . '.xlsx';
        return response(HojaCalculo::xlsx($hoja, 'Retenciones'), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
        ]);
    }

    // Certificado de retención en la fuente para un tercero (año o meses elegidos)
    public function certificado(Request $r, ContableCliente $cliente)
    {
        $this->mio($cliente);
        $nit = Retenciones::nit($r->query('nit'));
        $anio = (int) $r->query('anio', now()->year);
        abort_if($nit === '', 404);
        $tercero = ContableTercero::where('cliente_id', $cliente->id)->where('nit', $nit)->first();
        $por = [];
        foreach (ContablePeriodo::where('cliente_id', $cliente->id)->where('anio', $anio)->orderBy('mes')->get() as $p) {
            foreach (Retenciones::analisis($p) as $f) {
                if ($f['nit'] !== $nit || $f['direccion'] !== 'recibida') continue;
                $k = $f['concepto'] ?? 'otros';
                $por[$k] ??= ['base' => 0, 'ret' => 0, 'rete_iva' => 0, 'rete_ica' => 0];
                $por[$k]['base'] += $f['base'];
                $por[$k]['ret'] += $f['practicada'];
                $por[$k]['rete_iva'] += $f['rete_iva'];
                $por[$k]['rete_ica'] += $f['rete_ica'];
            }
        }
        return view('contabilidad.certificado', compact('cliente', 'tercero', 'nit', 'anio', 'por'));
    }

    // ===================== Tarifas =====================

    public function tarifas()
    {
        Retenciones::asegurarTarifas();
        return view('contabilidad.tarifas', ['tarifas' => ContableTarifa::orderBy('concepto')->orderBy('desde')->get(), 'uvt' => Retenciones::UVT]);
    }

    public function tarifasGuardar(Request $r)
    {
        $r->validate([
            't' => 'array', 't.*.concepto' => ['required', Rule::in(array_keys(Retenciones::CONCEPTOS))],
            't.*.nombre' => 'required|string|max:100', 't.*.base_uvt' => 'required|numeric|min:0',
            't.*.tarifa_declarante' => 'required|numeric|min:0|max:100', 't.*.tarifa_no_declarante' => 'required|numeric|min:0|max:100',
            't.*.desde' => 'nullable|date', 't.*.hasta' => 'nullable|date',
        ]);
        DB::transaction(function () use ($r) {
            $ids = [];
            foreach ((array) $r->input('t', []) as $i => $x) {
                if (!empty($x['borrar'])) continue;
                $datos = ['concepto' => $x['concepto'], 'nombre' => $x['nombre'], 'base_uvt' => $x['base_uvt'], 'tarifa_declarante' => $x['tarifa_declarante'],
                    'tarifa_no_declarante' => $x['tarifa_no_declarante'], 'desde' => $x['desde'] ?: null, 'hasta' => $x['hasta'] ?: null, 'orden' => $i];
                $t = !empty($x['id']) ? ContableTarifa::find($x['id']) : null;
                if ($t) $t->update($datos); else $t = ContableTarifa::create($datos);
                $ids[] = $t->id;
            }
            ContableTarifa::whereNotIn('id', $ids)->delete();
        });
        return back()->with('success', 'Tabla de retenciones guardada.');
    }

    public function tarifasRestaurar()
    {
        ContableTarifa::query()->delete();
        Retenciones::asegurarTarifas();
        return back()->with('success', 'Se restauró la tabla general.');
    }
}
