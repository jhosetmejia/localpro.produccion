<?php

namespace App\Http\Controllers;

use App\Support\HojaCalculo;
use App\Support\ImportarClientes;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

// Subir un Excel/CSV de personas y convertirlas en clientes en un instante
class importarClientesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:crear-cliente');
    }

    public function index()
    {
        return view('cliente.importar');
    }

    public function plantilla()
    {
        $filas = [
            ['Nombre', 'Cédula', 'Celular', 'Correo', 'Dirección'],
            ['María Fernanda López', '1085123456', '3001234567', 'maria@correo.com', 'Calle 18 # 25-30, Pasto'],
            ['Carlos Andrés Rosero', '12987654', '3159876543', '', 'Barrio Las Cuadras'],
        ];
        try {
            $bin = HojaCalculo::xlsx($filas, 'Clientes', [28, 16, 16, 26, 32]);
            return response($bin, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="plantilla-clientes-localpro.xlsx"',
            ]);
        } catch (Throwable $e) {
            $csv = "\xEF\xBB\xBF" . collect($filas)->map(fn ($f) => implode(';', $f))->implode("\r\n");
            return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="plantilla-clientes-localpro.csv"']);
        }
    }

    // Paso 1: subir el archivo, leerlo y detectar las columnas
    public function subir(Request $request)
    {
        $request->validate(['archivo' => 'required|file|max:10240'], [
            'archivo.required' => 'Elija el archivo de Excel o CSV.',
            'archivo.max' => 'El archivo pesa más de 10 MB.',
        ]);
        $f = $request->file('archivo');
        $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension());
        if (!in_array($ext, ['xlsx', 'xlsm', 'xls', 'csv', 'txt', 'tsv'], true)) {
            return back()->withErrors('Suba un archivo de Excel (.xlsx) o CSV.');
        }
        $ruta = $f->storeAs('importaciones/' . TiendaActual::id(), 'clientes-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.' . $ext, 'local');
        try {
            $filas = HojaCalculo::leer(Storage::disk('local')->path($ruta), $ext);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($ruta);
            return back()->withErrors($e->getMessage());
        }
        Storage::disk('local')->delete($ruta);

        $a = ImportarClientes::analizar($filas);
        if (!$a['datos']) {
            return back()->withErrors('El archivo está vacío o no tiene filas con datos.');
        }
        if (count($a['datos']) > 5000) {
            return back()->withErrors('El archivo tiene más de 5.000 personas. Divídalo en partes y súbalas una por una.');
        }
        $clave = 'clientes-' . Str::random(16) . '.json';
        Storage::disk('local')->put('importaciones/' . TiendaActual::id() . '/' . $clave, json_encode($a, JSON_UNESCAPED_UNICODE));
        session(['importar_clientes' => $clave, 'importar_clientes_nombre' => mb_substr($f->getClientOriginalName(), 0, 120)]);

        return redirect()->route('clientes.importar.revisar');
    }

    // Paso 2: ver qué encontró y ajustar las columnas si hace falta
    public function revisar(Request $request)
    {
        $a = $this->cargar();
        if (!$a) return redirect()->route('clientes.importar')->withErrors('Suba de nuevo el archivo.');

        $mapa = $a['mapa'];
        if ($request->has('m')) {
            $mapa = collect((array) $request->query('m'))->only(array_keys(ImportarClientes::CAMPOS))
                ->filter(fn ($v) => $v !== '' && $v !== null)->map(fn ($v) => (int) $v)->all();
        }
        $clientes = ImportarClientes::filas($a['datos'], $mapa);
        $validos = collect($clientes)->filter(fn ($c) => $c['nombre'] !== '' && ($c['documento'] !== '' || $c['telefono'] !== '' || $c['email'] !== ''));

        return view('cliente.importar-revisar', [
            'titulos' => $a['titulos'],
            'mapa' => $mapa,
            'muestra' => array_slice($clientes, 0, 8),
            'total' => count($clientes),
            'validos' => $validos->count(),
            'conTel' => $validos->where('telefono', '<>', '')->count(),
            'conMail' => $validos->where('email', '<>', '')->count(),
            'archivo' => session('importar_clientes_nombre'),
        ]);
    }

    // Paso 3: crear los clientes
    public function confirmar(Request $request)
    {
        $a = $this->cargar();
        if (!$a) return redirect()->route('clientes.importar')->withErrors('Suba de nuevo el archivo.');
        $mapa = collect((array) $request->input('m'))->only(array_keys(ImportarClientes::CAMPOS))
            ->filter(fn ($v) => $v !== '' && $v !== null)->map(fn ($v) => (int) $v)->all();
        if (!isset($mapa['nombre'])) {
            return back()->withErrors('Elija cuál columna tiene el nombre.');
        }
        $r = ImportarClientes::guardar(ImportarClientes::filas($a['datos'], $mapa));
        $this->borrar();

        $msg = $r['creados'] === 1 ? '¡Listo! Se creó 1 cliente.' : '¡Listo! Se crearon ' . number_format($r['creados'], 0, ',', '.') . ' clientes.';
        if ($r['repetidos']) $msg .= ' ' . $r['repetidos'] . ' ya existían y no se duplicaron.';
        if ($r['sin_datos']) $msg .= ' ' . $r['sin_datos'] . ' filas sin nombre o sin cédula/celular/correo se saltaron.';
        return redirect()->route('clientes.index')->with('success', $msg);
    }

    public function cancelar()
    {
        $this->borrar();
        return redirect()->route('clientes.importar');
    }

    private function cargar(): ?array
    {
        $clave = session('importar_clientes');
        $ruta = 'importaciones/' . TiendaActual::id() . '/' . $clave;
        if (!$clave || !Storage::disk('local')->exists($ruta)) return null;
        return json_decode(Storage::disk('local')->get($ruta), true) ?: null;
    }

    private function borrar(): void
    {
        if ($clave = session('importar_clientes')) {
            Storage::disk('local')->delete('importaciones/' . TiendaActual::id() . '/' . $clave);
        }
        session()->forget(['importar_clientes', 'importar_clientes_nombre']);
    }
}
