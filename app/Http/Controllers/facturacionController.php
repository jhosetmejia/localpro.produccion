<?php

namespace App\Http\Controllers;

use App\Models\DocumentoElectronico;
use App\Models\Negocio;
use App\Models\PosVenta;
use App\Models\Producto;
use App\Support\Dian\Catalogos;
use App\Support\Dian\Facturador;
use App\Support\Dian\Validador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Facturación electrónica DIAN del negocio: datos fiscales, resoluciones, revisión y documentos enviados
class facturacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:facturacion-dian');
    }

    public function index(Request $request)
    {
        $t = Negocio::datos();
        $docs = DocumentoElectronico::with('venta:id,numero,cliente_nombre')
            ->when($request->get('estado'), fn ($q, $e) => $q->where('estado', $e))
            ->latest('id')->paginate(15)->withQueryString();
        $conteo = DocumentoElectronico::selectRaw('estado, count(*) n')->groupBy('estado')->pluck('n', 'estado');
        return view('facturacion.index', [
            'negocio' => $t, 'prep' => Facturador::preparacion($t), 'docs' => $docs, 'conteo' => $conteo,
            'proveedor' => Facturador::nombreProveedor(), 'sinImpuesto' => Producto::where('estado', 1)->where('impuesto', 'ninguno')->count(),
            'revision' => session('revision'),
        ]);
    }

    public function update(Request $request)
    {
        $t = Negocio::datos();
        $d = $request->validate([
            'razon_social' => 'nullable|string|max:120',
            'nit' => 'nullable|string|max:30',
            'dv' => 'nullable|digits:1',
            'tipo_persona' => 'nullable|in:natural,juridica',
            'responsabilidades' => 'nullable|array',
            'responsabilidades.*' => Rule::in(array_keys(Catalogos::RESPONSABILIDADES)),
            'ciiu' => 'nullable|string|max:10',
            'municipio_dane' => 'nullable|digits:5',
            'direccion' => 'nullable|string|max:120',
            'email_facturacion' => 'nullable|email|max:120',
            'pos_electronico' => 'nullable|boolean',
            'resolucion_numero' => 'nullable|string|max:40',
            'resolucion_fecha' => 'nullable|date',
            'resolucion_vigencia' => 'nullable|date',
            'resolucion_prefijo' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'resolucion_desde' => 'nullable|integer|min:1',
            'resolucion_hasta' => 'nullable|integer|min:1|gte:resolucion_desde',
            'fe_activa' => 'nullable|boolean',
            'fe_resolucion_numero' => 'nullable|string|max:40',
            'fe_resolucion_fecha' => 'nullable|date',
            'fe_resolucion_vigencia' => 'nullable|date',
            'fe_prefijo' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'fe_desde' => 'nullable|integer|min:1',
            'fe_hasta' => 'nullable|integer|min:1|gte:fe_desde',
            'fe_clave_tecnica' => 'nullable|string|max:120',
            'fe_ambiente' => 'nullable|in:pruebas,produccion',
        ], [
            'municipio_dane.digits' => 'El código del municipio tiene 5 números (Pasto: 52001).',
            'resolucion_hasta.gte' => 'El rango "hasta" debe ser mayor que "desde".',
            'fe_hasta.gte' => 'El rango "hasta" debe ser mayor que "desde".',
        ]);

        $nit = Catalogos::nitLimpio($d['nit'] ?? $t->nit);
        $d['nit'] = $nit ?: null;
        // El DV se calcula solo si no lo escribieron
        $d['dv'] = ($d['dv'] ?? null) ?: Catalogos::dv($nit);
        $d['responsabilidades'] = implode(',', $d['responsabilidades'] ?? []) ?: null;
        $d['regimen'] = match (true) {
            str_contains((string) $d['responsabilidades'], 'O-48') => 'Responsable de IVA',
            str_contains((string) $d['responsabilidades'], 'O-47') => 'Régimen simple de tributación',
            $d['responsabilidades'] !== null => 'No responsable de IVA',
            default => $t->regimen,
        };
        $d['pos_electronico'] = $request->boolean('pos_electronico');
        $d['fe_activa'] = $request->boolean('fe_activa');
        if (blank($d['fe_clave_tecnica'] ?? null)) unset($d['fe_clave_tecnica']); // vacío = conserva la guardada
        // Pasar a producción lo hace LocalPro cuando la DIAN habilita al negocio
        if (!auth()->user()->es_superadmin) unset($d['fe_ambiente']);

        // Al activar, la numeración tiene que estar completa
        if ($d['pos_electronico'] && (blank($d['resolucion_numero']) || blank($d['resolucion_prefijo']) || blank($d['resolucion_desde']) || blank($d['resolucion_hasta']))) {
            return back()->withInput()->withErrors(['pos_electronico' => 'Para activar el POS electrónico complete la resolución POS: número, prefijo y rango.']);
        }
        if ($d['fe_activa'] && (blank($d['fe_resolucion_numero']) || blank($d['fe_prefijo']) || blank($d['fe_desde']) || blank($d['fe_hasta']) || (blank($t->fe_clave_tecnica) && blank($request->fe_clave_tecnica)))) {
            return back()->withInput()->withErrors(['fe_activa' => 'Para activar la factura electrónica complete la resolución: número, prefijo, rango y clave técnica.']);
        }

        $t->update($d);
        return back()->with('success', 'Datos de facturación guardados.');
    }

    // Revisa la última venta como si fuera a salir a la DIAN (no envía ni guarda nada)
    public function revisar()
    {
        $t = Negocio::datos();
        $v = PosVenta::where('estado', 'pagada')->latest('id')->first();
        if (!$v) return back()->withErrors(['revisar' => 'Aún no tiene ventas para revisar. Haga una venta en la caja y vuelva.']);
        $tipo = $t->fe_activa ? 'fe' : 'pos_e';
        [$pref, $cons] = Facturador::partirNumero($v->numero);
        $doc = new DocumentoElectronico(['tienda_id' => $t->id, 'tipo' => $tipo, 'pos_venta_id' => $v->id, 'numero' => $v->numero, 'prefijo' => $pref, 'consecutivo' => $cons, 'ambiente' => $t->fe_ambiente ?: 'pruebas']);
        $doc->created_at = $v->created_at;
        $p = Facturador::payload($doc);
        if ($tipo === 'fe' && ($p['adquiriente']['numero'] ?? '') === Catalogos::CONSUMIDOR_FINAL['numero']) {
            // La revisión de factura usa un cliente de ejemplo si la venta fue a consumidor final
            $p['adquiriente'] = ['tipo_doc' => '13', 'numero' => '1000000000', 'nombre' => 'Cliente de ejemplo', 'email' => 'cliente@ejemplo.com'] + Catalogos::CONSUMIDOR_FINAL;
        }
        $err = Validador::errores($p);
        return back()->with('revision', ['venta' => $v->numero, 'tipo' => $tipo, 'errores' => $err, 'total' => $p['totales']['total'], 'base' => $p['totales']['base'], 'impuestos' => $p['totales']['impuestos']]);
    }

    public function reintentar(DocumentoElectronico $documento)
    {
        abort_if(in_array($documento->estado, ['aceptado'], true), 422);
        $documento->forceFill(['estado' => 'pendiente'])->save();
        Facturador::enviar($documento);
        $documento->refresh();
        $msg = $documento->numero . ': ' . $documento->estado_info[0] . ($documento->ultimo_error ? ' — ' . $documento->ultimo_error : '');
        return in_array($documento->estado, ['error', 'rechazado'], true) ? back()->withErrors(['doc' => $msg]) : back()->with('success', $msg);
    }

    // Pone el mismo impuesto a todos los productos que no lo tienen definido
    public function impuestoMasivo(Request $request)
    {
        $d = $request->validate(['impuesto' => ['required', Rule::in(array_keys(Catalogos::IMPUESTOS))]]);
        $n = Producto::where('impuesto', 'ninguno')->update(['impuesto' => $d['impuesto']]);
        return back()->with('success', "Se actualizó el impuesto de $n productos a " . Catalogos::impuesto($d['impuesto'])[0] . '.');
    }

    public function documento(DocumentoElectronico $documento)
    {
        return view('facturacion.documento', ['doc' => $documento->load('venta', 'referencia')]);
    }
}
