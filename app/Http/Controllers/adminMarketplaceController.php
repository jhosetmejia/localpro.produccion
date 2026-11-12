<?php

namespace App\Http\Controllers;

use App\Support\TemaMarketplace;
use Illuminate\Http\Request;

/**
 * Súper administrador: configuración del marketplace. Por ahora, el tema visual
 * (Normal o Navidad) que usan el marketplace y todas las tiendas en línea.
 */
class adminMarketplaceController extends Controller
{
    public function index()
    {
        return view('admin.marketplace', ['tema' => TemaMarketplace::actual(), 'temas' => TemaMarketplace::TEMAS]);
    }

    public function guardar(Request $request)
    {
        $d = $request->validate(['tema' => 'required|in:' . implode(',', array_keys(TemaMarketplace::TEMAS))], [
            'tema.required' => 'Elija un tema.',
            'tema.in' => 'Ese tema no existe.',
        ]);
        TemaMarketplace::guardar($d['tema']);
        return back()->with('success', 'Listo: el marketplace y las tiendas quedaron con el tema ' . TemaMarketplace::TEMAS[$d['tema']][0] . '.');
    }
}
