<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\RedContacto;
use App\Support\HojaCalculo;
use App\Support\ImportarClientes;
use App\Support\RedContactos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

// Administrador: base de contactos de LocalPro para todos los asesores (a cada persona se le escribe una sola vez)
class adminRedContactosController extends Controller
{
    public function index(Request $request)
    {
        $ver = $request->query('ver', 'contactados');
        $q = trim((string) $request->query('q'));
        $hoy = now()->toDateString();
        $lista = RedContacto::with(['contactadoPor:id,nombre,codigo', 'asignadoA:id,nombre,codigo'])
            ->when($ver === 'contactados', fn ($x) => $x->whereNotNull('contactado_at')->orderByDesc('contactado_at'))
            ->when($ver === 'por_contactar', fn ($x) => $x->whereNull('contactado_at')->orderBy('id'))
            ->when($ver === 'hoy', fn ($x) => $x->where('asignado_fecha', $hoy)->orderByDesc('contactado_at'))
            ->when($ver === 'todos', fn ($x) => $x->orderByDesc('id'))
            ->when($q !== '', fn ($x) => $x->where(fn ($y) => $y->where('nombre', 'like', "%$q%")->orWhere('telefono', 'like', '%' . preg_replace('/\D/', '', $q) . '%')))
            ->paginate(50)->withQueryString();

        $porAsesor = RedContacto::with('contactadoPor:id,nombre,codigo')->whereNotNull('contactado_por')
            ->selectRaw('contactado_por, count(*) n, sum(date(contactado_at) = ?) hoy', [$hoy])
            ->groupBy('contactado_por')->orderByDesc('n')->limit(30)->get();

        return view('admin.red.contactos', [
            'lista' => $lista, 'ver' => $ver, 'q' => $q, 'porAsesor' => $porAsesor, 'diarios' => RedContactos::diarios(),
            'kpi' => [
                'total' => RedContacto::count(),
                'contactados' => RedContacto::whereNotNull('contactado_at')->count(),
                'por' => RedContacto::whereNull('contactado_at')->count(),
                'hoy' => RedContacto::whereDate('contactado_at', $hoy)->count(),
            ],
        ]);
    }

    public function subir(Request $request)
    {
        $request->validate(['archivo' => 'required|file|max:20480'], ['archivo.required' => 'Elija el archivo de contactos.', 'archivo.max' => 'El archivo pesa más de 20 MB.']);
        $f = $request->file('archivo');
        $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension());
        if (!in_array($ext, ['vcf', 'vcard', 'csv', 'txt', 'xlsx'], true)) return back()->withErrors('Suba un Excel (.xlsx), un .csv o el archivo de contactos del celular (.vcf).');
        try {
            $filas = HojaCalculo::leer($f->getRealPath(), $ext);
        } catch (Throwable $e) {
            return back()->withErrors($e->getMessage());
        }
        $an = ImportarClientes::analizar($filas);
        if (!isset($an['mapa']['telefono']) || $an['mapa']['telefono'] === '') return back()->withErrors('No encontramos la columna del celular en el archivo.');

        $ya = RedContacto::pluck('telefono')->flip()->all();
        $lote = mb_substr($f->getClientOriginalName(), 0, 80);
        $nuevas = [];
        $repetidos = 0;
        foreach (ImportarClientes::filas($an['datos'], $an['mapa']) as $c) {
            if ($c['telefono'] === '' || strlen($c['telefono']) < 7) continue;
            if (isset($ya[$c['telefono']])) { $repetidos++; continue; }
            $ya[$c['telefono']] = 1;
            $nuevas[] = ['nombre' => mb_substr($c['nombre'] ?: 'Contacto ' . $c['telefono'], 0, 80), 'telefono' => $c['telefono'], 'lote' => $lote, 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($nuevas, 500) as $parte) DB::table('red_contactos')->insertOrIgnore($parte);
        $n = count($nuevas);
        return back()->with($n ? 'success' : 'error', $n
            ? "¡Listo! Se agregaron $n contactos para los asesores." . ($repetidos ? " $repetidos ya estaban en la base y no se repitieron." : '')
            : 'No hay contactos nuevos con celular en ese archivo' . ($repetidos ? " ($repetidos ya estaban en la base)." : '.'));
    }

    public function ajustes(Request $request)
    {
        $d = $request->validate(['diarios' => 'required|integer|min:1|max:200'], ['diarios.required' => 'Escriba cuántos contactos al día.']);
        Ajuste::guardar('red_contactos_diarios', $d['diarios']);
        return back()->with('success', 'Guardado: ' . $d['diarios'] . ' contactos al día por asesor.');
    }

    // Quitar la marca de contactado (si un asesor se equivocó): vuelve a la base para repartirse
    public function liberar(RedContacto $contacto)
    {
        $contacto->update(['contactado_at' => null, 'contactado_por' => null, 'estado' => null, 'asignado_a' => null, 'asignado_fecha' => null]);
        return back()->with('success', $contacto->nombre . ' vuelve a quedar por contactar.');
    }
}
