<?php

namespace App\Http\Controllers;

use App\Models\RedCuenta;
use App\Models\RedPublicacion;
use App\Models\Tienda;
use Illuminate\Http\Request;

// Administrador LocalPro: las redes conectadas de todos los negocios, y entrar a publicar o responder por cualquiera
class adminRedesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $cuentas = RedCuenta::withoutGlobalScopes()->where('estado', '<>', 'desconectada')->get()->groupBy('tienda_id');
        $tiendas = Tienda::whereIn('id', $cuentas->keys())
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombre', 'like', "%$q%")->orWhere('codigo', 'like', "%$q%")))
            ->orderBy('nombre')->get();
        $pubs = RedPublicacion::withoutGlobalScopes()->whereIn('tienda_id', $tiendas->pluck('id'))
            ->selectRaw("tienda_id, SUM(estado = 'programada') prog, SUM(estado IN ('error','parcial')) mal, SUM(estado IN ('pendiente','procesando')) subiendo, MAX(COALESCE(publicada_en, created_at)) ultima")
            ->groupBy('tienda_id')->get()->keyBy('tienda_id');
        return view('admin.redes', ['tiendas' => $tiendas, 'cuentas' => $cuentas, 'pubs' => $pubs, 'q' => $q]);
    }

    // Entra al negocio (como "Ver el sistema como la tienda") y abre Publicar, Mensajes o Cuentas
    public function abrir(Request $request, Tienda $tienda)
    {
        $request->session()->put('tienda_visitada', $tienda->id);
        $ir = ['mensajes' => 'redes.index', 'cuentas' => 'redes.cuentas'][$request->input('ir')] ?? 'redes.publicar';
        return redirect()->route($ir)->with('info', 'Está publicando como ' . $tienda->nombre . '. Para volver, use el selector de vista del administrador.');
    }
}
