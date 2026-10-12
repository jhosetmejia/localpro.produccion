<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\DocumentoElectronico;
use App\Models\Tienda;
use App\Support\Dian\Facturador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

// Administrador: proveedor tecnológico de facturación electrónica y estado de todas las tiendas
class adminFacturacionController extends Controller
{
    public function index()
    {
        $tiendas = Tienda::where(fn ($q) => $q->whereNull('pais')->orWhere('pais', 'CO'))->orderBy('nombre')->get();
        $docs = DocumentoElectronico::withoutGlobalScopes()->selectRaw('tienda_id, estado, count(*) n')->groupBy('tienda_id', 'estado')->get()->groupBy('tienda_id');
        return view('admin.facturacion', [
            'tiendas' => $tiendas, 'docs' => $docs,
            'proveedor' => Ajuste::valor('fe_proveedor'), 'nombre' => Ajuste::valor('fe_nombre'), 'url' => Ajuste::valor('fe_url'),
            'tieneToken' => (bool) Ajuste::valor('fe_token'), 'nombreProveedor' => Facturador::nombreProveedor(),
            'cola' => DocumentoElectronico::withoutGlobalScopes()->whereIn('estado', ['pendiente', 'error'])->count(),
        ]);
    }

    public function guardar(Request $request)
    {
        $d = $request->validate([
            'fe_proveedor' => 'nullable|in:,simulado,api',
            'fe_nombre' => 'nullable|string|max:60',
            'fe_url' => 'nullable|url|max:200|required_if:fe_proveedor,api',
            'fe_token' => 'nullable|string|max:500',
        ], ['fe_url.required_if' => 'Para conectar por API escriba la dirección del proveedor.']);
        if (($d['fe_proveedor'] ?? '') === 'api' && !$request->filled('fe_token') && !Ajuste::valor('fe_token')) {
            return back()->withInput()->withErrors(['fe_token' => 'Escriba el token que le dio el proveedor.']);
        }
        Ajuste::guardar('fe_proveedor', $d['fe_proveedor'] ?? '');
        Ajuste::guardar('fe_nombre', $d['fe_nombre'] ?? null);
        Ajuste::guardar('fe_url', $d['fe_url'] ?? null);
        if ($request->filled('fe_token')) Ajuste::guardar('fe_token', Crypt::encryptString($request->fe_token));
        return back()->with('success', 'Proveedor de facturación guardado: ' . Facturador::nombreProveedor() . '.');
    }

    // Envía ya la cola (sin esperar al cron)
    public function enviarCola()
    {
        \Illuminate\Support\Facades\Artisan::call('dian:enviar');
        return back()->with('success', trim(\Illuminate\Support\Facades\Artisan::output()));
    }
}
