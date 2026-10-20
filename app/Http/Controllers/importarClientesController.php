<?php

namespace App\Http\Controllers;

use App\Support\HojaCalculo;
use App\Support\ImportarClientes;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    /**
     * Textos y rutas de lo que se importa. El mismo importador inteligente sirve para clientes y proveedores
     * (importarProveedoresController solo cambia esto).
     */
    protected function cfg(): array
    {
        return [
            'tipo' => 'clientes', 'relacion' => 'cliente', 'ruta' => 'clientes', 'icono' => 'fa-address-book',
            'singular' => 'cliente', 'plural' => 'clientes', 'Plural' => 'Clientes', 'volver' => 'Mis clientes',
            'quien' => 'personas', 'ejemplo' => 'el Excel de su sistema anterior, una lista de WhatsApp, una base de datos de un evento…',
            'listo' => 'En un instante quedan listos para venderles y escribirles por WhatsApp.',
        ];
    }

    public function index()
    {
        return view('cliente.importar', ['cfg' => $this->cfg()]);
    }

    // Crear una base de datos desde cero con los contactos del celular
    public function contactos()
    {
        return view('cliente.contactos');
    }

    public function plantilla()
    {
        $filas = [
            ['Nombre', 'Cédula', 'Celular', 'Correo', 'Dirección'],
            ['María Fernanda López', '1085123456', '3001234567', 'maria@correo.com', 'Calle 18 # 25-30, Pasto'],
            ['Carlos Andrés Rosero', '12987654', '3159876543', '', 'Barrio Las Cuadras'],
        ];
        $c = $this->cfg();
        if ($c['tipo'] === 'proveedores') {
            $filas = [
                ['Nombre o empresa', 'NIT / Cédula', 'Celular', 'Correo', 'Dirección'],
                ['Textiles del Sur S.A.S.', '900123456-7', '3001234567', 'ventas@textilesdelsur.com', 'Cra 27 # 15-40, Pasto'],
                ['Confecciones Rosero', '12987654', '3159876543', '', 'Medellín'],
            ];
        }
        try {
            $bin = HojaCalculo::xlsx($filas, $c['Plural'], [28, 16, 16, 26, 32]);
            return response($bin, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="plantilla-' . $c['tipo'] . '-localpro.xlsx"',
            ]);
        } catch (Throwable $e) {
            $csv = "\xEF\xBB\xBF" . collect($filas)->map(fn ($f) => implode(';', $f))->implode("\r\n");
            return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="plantilla-' . $c['tipo'] . '-localpro.csv"']);
        }
    }

    // Paso 1: subir el archivo, leerlo y detectar las columnas
    public function subir(Request $request)
    {
        $c = $this->cfg();
        $enlace = trim((string) $request->input('enlace'));
        if (!$request->hasFile('archivo') && $enlace !== '') {
            // Hoja de Google: se descarga como CSV (la hoja debe estar compartida "con cualquier persona que tenga el enlace")
            if (!preg_match('#docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)#', $enlace, $m)) {
                return back()->withErrors('Pegue el enlace de una hoja de Google (docs.google.com/spreadsheets/…).');
            }
            $gid = preg_match('/[#&?]gid=(\d+)/', $enlace, $g) ? '&gid=' . $g[1] : '';
            try {
                $res = Http::timeout(30)->get('https://docs.google.com/spreadsheets/d/' . $m[1] . '/export?format=csv' . $gid);
            } catch (Throwable $e) {
                return back()->withErrors('No pudimos abrir la hoja de Google. Revise el enlace o descárguela como Excel y súbala.');
            }
            if (!$res->successful() || str_contains(strtolower((string) $res->header('Content-Type')), 'html')) {
                return back()->withErrors('La hoja de Google no está compartida. En Google Sheets toque Compartir → "Cualquier persona con el enlace" y vuelva a pegarlo (o descárguela como Excel y súbala).');
            }
            $ruta = 'importaciones/' . TiendaActual::id() . '/' . $c['tipo'] . '-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.csv';
            Storage::disk('local')->put($ruta, $res->body());
            $ext = 'csv';
            $nombreArchivo = 'Hoja de Google';
        } else {
            $request->validate(['archivo' => 'required|file|max:10240'], [
                'archivo.required' => 'Elija el archivo de Excel, CSV o de contactos (.vcf), o pegue el enlace de una hoja de Google.',
                'archivo.max' => 'El archivo pesa más de 10 MB.',
            ]);
            $f = $request->file('archivo');
            $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension());
            if (!in_array($ext, ['xlsx', 'xlsm', 'xls', 'csv', 'txt', 'tsv', 'vcf', 'vcard'], true)) {
                return back()->withErrors('Suba un archivo de Excel (.xlsx), CSV o de contactos del celular (.vcf), o pegue el enlace de una hoja de Google.');
            }
            $ruta = $f->storeAs('importaciones/' . TiendaActual::id(), $c['tipo'] . '-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.' . $ext, 'local');
            $nombreArchivo = $f->getClientOriginalName();
        }
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
        // Archivos grandes: el sistema los divide solo y los importa de a 5.000
        $a['desde'] = 0;
        $clave = $c['tipo'] . '-' . Str::random(16) . '.json';
        Storage::disk('local')->put('importaciones/' . TiendaActual::id() . '/' . $clave, json_encode($a, JSON_UNESCAPED_UNICODE));
        session([$this->sk() => $clave, $this->sk() . '_nombre' => mb_substr($nombreArchivo, 0, 120)]);

        $total = count($a['datos']);
        return redirect()->route($c['ruta'] . '.importar.revisar')->with($total > self::LOTE ? 'info' : 'ninguno',
            'Su archivo tiene ' . number_format($total, 0, ',', '.') . ' registros. Los importamos por partes de ' . number_format(self::LOTE, 0, ',', '.') . ': primero estos ' . number_format(self::LOTE, 0, ',', '.') . ' y luego le mostramos los siguientes.');
    }

    private const LOTE = 5000;

    // Clave de sesión de esta importación (clientes y proveedores no se pisan)
    private function sk(): string
    {
        return 'importar_' . $this->cfg()['tipo'];
    }

    // La parte que toca ahora (de a 5.000)
    private function lote(array $a): array
    {
        return array_slice($a['datos'], (int) ($a['desde'] ?? 0), self::LOTE);
    }

    private function parte(array $a): array
    {
        $total = count($a['datos']);
        $desde = (int) ($a['desde'] ?? 0);
        return ['total' => $total, 'desde' => $desde + 1, 'hasta' => min($total, $desde + self::LOTE), 'partes' => (int) ceil($total / self::LOTE), 'n' => intdiv($desde, self::LOTE) + 1];
    }

    // Paso 2: ver qué encontró y ajustar las columnas si hace falta
    public function revisar(Request $request)
    {
        $a = $this->cargar();
        if (!$a) return redirect()->route($this->cfg()['ruta'] . '.importar')->withErrors('Suba de nuevo el archivo.');

        $mapa = $a['mapa'];
        if ($request->has('m')) {
            $mapa = collect((array) $request->query('m'))->only(array_keys(ImportarClientes::CAMPOS))
                ->filter(fn ($v) => $v !== '' && $v !== null)->map(fn ($v) => (int) $v)->all();
        }
        $clientes = ImportarClientes::filas($this->lote($a), $mapa);
        $validos = collect($clientes)->filter(fn ($c) => $c['nombre'] !== '' && ($c['documento'] !== '' || $c['telefono'] !== '' || $c['email'] !== ''));

        return view('cliente.importar-revisar', [
            'titulos' => $a['titulos'],
            'mapa' => $mapa,
            'muestra' => array_slice($clientes, 0, 8),
            'total' => count($clientes),
            'validos' => $validos->count(),
            'conTel' => $validos->where('telefono', '<>', '')->count(),
            'conMail' => $validos->where('email', '<>', '')->count(),
            'archivo' => session($this->sk() . '_nombre'),
            'parte' => $this->parte($a),
            'cfg' => $this->cfg(),
        ]);
    }

    // Paso 3: crear los clientes
    public function confirmar(Request $request)
    {
        $a = $this->cargar();
        if (!$a) return redirect()->route($this->cfg()['ruta'] . '.importar')->withErrors('Suba de nuevo el archivo.');
        $mapa = collect((array) $request->input('m'))->only(array_keys(ImportarClientes::CAMPOS))
            ->filter(fn ($v) => $v !== '' && $v !== null)->map(fn ($v) => (int) $v)->all();
        if (!isset($mapa['nombre'])) {
            return back()->withErrors('Elija cuál columna tiene el nombre.');
        }
        $c = $this->cfg();
        $r = ImportarClientes::guardar(ImportarClientes::filas($this->lote($a), $mapa), $c['relacion']);

        $msg = $r['creados'] === 1 ? '¡Listo! Se creó 1 ' . $c['singular'] . '.' : '¡Listo! Se crearon ' . number_format($r['creados'], 0, ',', '.') . ' ' . $c['plural'] . '.';
        if ($r['repetidos']) $msg .= ' ' . $r['repetidos'] . ' ya existían y no se duplicaron.';
        if ($r['sin_datos']) $msg .= ' ' . $r['sin_datos'] . ' filas sin nombre o sin cédula/celular/correo se saltaron.';

        // ¿Quedan más? Se pasa a la siguiente parte con las mismas columnas
        $sig = (int) ($a['desde'] ?? 0) + self::LOTE;
        if ($sig < count($a['datos'])) {
            $a['desde'] = $sig;
            $a['mapa'] = $mapa;
            Storage::disk('local')->put('importaciones/' . TiendaActual::id() . '/' . session($this->sk()), json_encode($a, JSON_UNESCAPED_UNICODE));
            $faltan = count($a['datos']) - $sig;
            return redirect()->route($c['ruta'] . '.importar.revisar')->with('success', $msg . ' Faltan ' . number_format($faltan, 0, ',', '.') . ' registros: revise la siguiente parte y toque importar.');
        }
        $this->borrar();
        return redirect()->route($c['ruta'] . '.index')->with('success', $msg);
    }

    public function cancelar()
    {
        $this->borrar();
        return redirect()->route($this->cfg()['ruta'] . '.importar');
    }

    private function cargar(): ?array
    {
        $clave = session($this->sk());
        $ruta = 'importaciones/' . TiendaActual::id() . '/' . $clave;
        if (!$clave || !Storage::disk('local')->exists($ruta)) return null;
        return json_decode(Storage::disk('local')->get($ruta), true) ?: null;
    }

    private function borrar(): void
    {
        if ($clave = session($this->sk())) {
            Storage::disk('local')->delete('importaciones/' . TiendaActual::id() . '/' . $clave);
        }
        session()->forget([$this->sk(), $this->sk() . '_nombre']);
    }
}
