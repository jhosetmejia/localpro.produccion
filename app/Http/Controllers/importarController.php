<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Importacion;
use App\Support\HojaCalculo;
use App\Support\ImportarProductos;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

// Importar productos desde Excel/CSV (el negocio solo o con ayuda del equipo de LocalPro)
class importarController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('crear-producto')), 403);
            return $next($request);
        });
    }

    public function index()
    {
        $tienda = TiendaActual::tienda();
        return view('producto.importar', [
            'tienda' => $tienda,
            'historial' => Importacion::where('tienda_id', $tienda->id)->latest()->take(10)->get(),
            'soporte' => preg_replace('/\D/', '', (string) Ajuste::valor('soporte_whatsapp')),
        ]);
    }

    public function plantilla()
    {
        $tallas = (bool) TiendaActual::tienda()?->usa_tallas;
        $filas = ImportarProductos::plantilla($tallas);
        try {
            $bin = HojaCalculo::xlsx($filas, 'Productos', $tallas ? [12, 28, 16, 14, 12, 11, 8, 12, 18] : [12, 28, 16, 14, 12, 11, 18]);
            return response($bin, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="plantilla-productos-localpro.xlsx"',
            ]);
        } catch (Throwable $e) {
            $csv = "\xEF\xBB\xBF" . collect($filas)->map(fn ($f) => implode(';', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $f)))->implode("\r\n");
            return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="plantilla-productos-localpro.csv"']);
        }
    }

    // Paso 1: subir el archivo y leerlo
    public function subir(Request $request)
    {
        $request->validate(['archivo' => 'required|file|max:10240'], [
            'archivo.required' => 'Elija el archivo de Excel o CSV.',
            'archivo.max' => 'El archivo pesa más de 10 MB.',
        ]);
        $f = $request->file('archivo');
        $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension());
        if (!in_array($ext, ['xlsx', 'xlsm', 'xls', 'csv', 'txt', 'tsv'], true)) {
            return back()->withErrors('Suba un archivo de Excel (.xlsx) o CSV. Si tiene otro formato (PDF, foto, sistema anterior), use "Que LocalPro lo haga por mí".');
        }
        $tienda = TiendaActual::tienda();
        $ruta = $f->storeAs('importaciones/' . $tienda->id, now()->format('Ymd-His') . '-' . Str::random(6) . '.' . $ext, 'local');

        $imp = Importacion::create([
            'tienda_id' => $tienda->id, 'user_id' => auth()->id(), 'tipo' => 'propia', 'estado' => 'pendiente',
            'archivo' => $ruta, 'nombre_original' => mb_substr($f->getClientOriginalName(), 0, 160),
        ]);
        return $this->preparar($imp);
    }

    // Lee el archivo de una importación y deja todo listo para revisar
    public static function preparar(Importacion $imp)
    {
        try {
            $filas = HojaCalculo::leer(Storage::disk('local')->path($imp->archivo), pathinfo($imp->archivo, PATHINFO_EXTENSION));
        } catch (Throwable $e) {
            return redirect()->route('productos.importar')->withErrors($e instanceof \RuntimeException ? $e->getMessage() : 'No se pudo leer el archivo. Revise que sea un Excel o CSV válido.');
        }
        if (count($filas) < 1) {
            return redirect()->route('productos.importar')->withErrors('El archivo está vacío.');
        }
        session(['importacion' => $imp->id]);
        return redirect()->route('productos.importar.revisar');
    }

    private function cargar(): array
    {
        $imp = Importacion::where('id', session('importacion'))->where('tienda_id', TiendaActual::id())->first();
        abort_unless($imp && $imp->archivo && Storage::disk('local')->exists($imp->archivo), 404);
        $filas = HojaCalculo::leer(Storage::disk('local')->path($imp->archivo), pathinfo($imp->archivo, PATHINFO_EXTENSION));
        return [$imp, $filas];
    }

    // Paso 2: revisar qué es cada columna
    public function revisar()
    {
        if (!session('importacion')) return redirect()->route('productos.importar');
        try {
            [$imp, $filas] = $this->cargar();
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('productos.importar')->withErrors('No se pudo abrir el archivo. Súbalo de nuevo.');
        }
        $conCab = ImportarProductos::tieneEncabezados($filas);
        $ancho = max(array_map('count', array_slice($filas, 0, 50)));
        $cab = $conCab ? $filas[0] : array_map(fn ($i) => 'Columna ' . ($i + 1), range(0, $ancho - 1));
        $cab = array_pad($cab, $ancho, '');
        $mapa = $conCab ? ImportarProductos::adivinar($cab) : [];

        return view('producto.importar-revisar', [
            'imp' => $imp,
            'cab' => $cab,
            'conCab' => $conCab,
            'mapa' => $mapa,
            'muestra' => array_slice($filas, $conCab ? 1 : 0, 8),
            'total' => count($filas) - ($conCab ? 1 : 0),
            'tienda' => TiendaActual::tienda(),
        ]);
    }

    // Paso 3: importar
    public function confirmar(Request $request)
    {
        [$imp, $filas] = $this->cargar();
        $mapa = array_filter((array) $request->input('mapa', []), fn ($c) => $c && isset(ImportarProductos::CAMPOS[$c]));
        // Un campo no puede estar en dos columnas
        if (count($mapa) !== count(array_unique($mapa))) {
            return back()->withErrors('Hay dos columnas con el mismo campo. Deje cada campo en una sola columna.');
        }
        if (!in_array('nombre', $mapa, true)) {
            return back()->withErrors('Indique cuál columna es el "Nombre del producto".');
        }
        if (!in_array('precio_venta', $mapa, true)) {
            return back()->withErrors('Indique cuál columna es el "Precio de venta".');
        }
        $conCab = $request->boolean('con_encabezados');
        if ($conCab) array_shift($filas);

        $res = ImportarProductos::importar($filas, $mapa, [
            'actualizar' => $request->boolean('actualizar'),
            'controla' => $request->boolean('controla'),
            'fila_inicial' => $conCab ? 2 : 1,
        ]);

        $resumen = "{$res['creados']} creados, {$res['actualizados']} actualizados, {$res['variantes']} tallas/colores, {$res['unidades']} unidades"
            . ($res['omitidos'] ? ", {$res['omitidos']} filas omitidas" : '');
        $imp->update([
            'estado' => 'lista', 'creados' => $res['creados'], 'actualizados' => $res['actualizados'],
            'resultado' => $resumen . ($res['errores'] ? "\n" . implode("\n", array_slice($res['errores'], 0, 50)) : ''),
            'atendido_por' => auth()->id(), 'atendido_at' => now(),
        ]);
        // Si era una solicitud al equipo, también queda lista
        if ($imp->tipo === 'solicitud' && auth()->user()->es_superadmin) {
            session()->forget(['importacion', 'tienda_visitada']);
            return redirect()->route('admin.importaciones')->with('success', 'Importación lista para ' . $imp->tienda->nombre . ': ' . $resumen . '.')->with('importacion_errores', $res['errores']);
        }
        session()->forget('importacion');
        return redirect()->route('productos.importar')->with('success', '¡Listo! ' . $resumen . '.')->with('importacion_errores', $res['errores']);
    }

    // "Que LocalPro lo haga por mí": suben cualquier archivo y el equipo lo importa
    public function solicitar(Request $request)
    {
        $d = $request->validate([
            'archivo' => 'required|file|max:20480|mimes:xlsx,xls,xlsm,csv,txt,pdf,jpg,jpeg,png,webp,zip,ods,doc,docx',
            'telefono' => 'required|string|max:20',
            'nota' => 'nullable|string|max:1000',
        ], [
            'archivo.required' => 'Adjunte el archivo con sus productos.',
            'archivo.mimes' => 'Formatos permitidos: Excel, CSV, PDF, fotos, Word o ZIP.',
            'archivo.max' => 'El archivo pesa más de 20 MB. Escríbanos por WhatsApp y se lo recibimos por allá.',
            'telefono.required' => 'Escriba un WhatsApp para contactarlo.',
        ]);
        $tienda = TiendaActual::tienda();
        $f = $request->file('archivo');
        $ext = strtolower($f->getClientOriginalExtension() ?: 'bin');
        $ruta = $f->storeAs('importaciones/' . $tienda->id, now()->format('Ymd-His') . '-equipo-' . Str::random(6) . '.' . $ext, 'local');
        Importacion::create([
            'tienda_id' => $tienda->id, 'user_id' => auth()->id(), 'tipo' => 'solicitud', 'estado' => 'pendiente',
            'archivo' => $ruta, 'nombre_original' => mb_substr($f->getClientOriginalName(), 0, 160),
            'telefono' => $d['telefono'], 'nota' => $d['nota'] ?? null,
        ]);
        return redirect()->route('productos.importar')->with('success', 'Recibimos su archivo. El equipo de LocalPro cargará sus productos y le avisará por WhatsApp cuando queden listos.');
    }

    public function cancelar()
    {
        session()->forget('importacion');
        return redirect()->route('productos.importar');
    }
}
