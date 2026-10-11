<?php

namespace App\Http\Controllers;

use App\Models\Importacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Panel LocalPro: negocios que pidieron que el equipo les cargue los productos
class adminImportacionController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->get('estado', 'pendiente');
        $lista = Importacion::with(['tienda:id,nombre,telefono', 'user:id,name,email'])
            ->when($estado !== 'todas', fn ($q) => $q->where('estado', $estado))
            ->when($request->get('tipo', 'solicitud') !== 'todas', fn ($q) => $q->where('tipo', $request->get('tipo', 'solicitud')))
            ->latest()->paginate(30)->withQueryString();
        return view('admin.importaciones', [
            'lista' => $lista,
            'estado' => $estado,
            'tipo' => $request->get('tipo', 'solicitud'),
            'pendientes' => Importacion::where('tipo', 'solicitud')->where('estado', 'pendiente')->count(),
        ]);
    }

    public function descargar(Importacion $importacion)
    {
        abort_unless($importacion->archivo && Storage::disk('local')->exists($importacion->archivo), 404);
        $nombre = ($importacion->tienda->nombre ?? 'tienda') . ' - ' . ($importacion->nombre_original ?: basename($importacion->archivo));
        return Storage::disk('local')->download($importacion->archivo, $nombre);
    }

    // Entra a la tienda y abre el importador con el archivo que mandaron
    public function atender(Request $request, Importacion $importacion)
    {
        $request->session()->put('tienda_visitada', $importacion->tienda_id);
        $ext = strtolower(pathinfo((string) $importacion->archivo, PATHINFO_EXTENSION));
        if (in_array($ext, ['xlsx', 'xlsm', 'csv', 'txt', 'tsv'], true)) {
            return importarController::preparar($importacion);
        }
        return redirect()->route('productos.importar')
            ->with('success', 'Está dentro de ' . ($importacion->tienda->nombre ?? 'la tienda') . '. Ese archivo (.' . $ext . ') no se puede leer solo: páselo a la plantilla de Excel y súbalo aquí, o cree los productos a mano.');
    }

    public function estado(Request $request, Importacion $importacion)
    {
        $d = $request->validate(['estado' => 'required|in:pendiente,lista,rechazada', 'resultado' => 'nullable|string|max:1000']);
        $importacion->update([
            'estado' => $d['estado'],
            'resultado' => $d['resultado'] ?? $importacion->resultado,
            'atendido_por' => auth()->id(),
            'atendido_at' => $d['estado'] === 'pendiente' ? null : now(),
        ]);
        return back()->with('success', 'Solicitud marcada como ' . $d['estado'] . '.');
    }
}
