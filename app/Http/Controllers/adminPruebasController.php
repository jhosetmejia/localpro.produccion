<?php

namespace App\Http\Controllers;

use App\Support\Pruebas\PruebaRestaurante;
use App\Support\Pruebas\RecorridoRestaurante;
use App\Support\RolesNegocio;
use Illuminate\Http\Request;

// Administrador LocalPro → Probar sistemas: elige el sistema, ve su ciclo de vida y lo prueba de punta a punta
class adminPruebasController extends Controller
{
    // Sistemas que se pueden probar (los demás salen como "próximamente")
    public const SISTEMAS = [
        'restaurante' => ['Restaurante / cafetería', 'fa-utensils', true],
        'ropa' => ['Tienda de ropa', 'fa-shirt', false],
        'alquiler' => ['Alquiler', 'fa-key', false],
        'funeraria' => ['Funeraria (obituarios)', 'fa-dove', false],
        'contable' => ['Contador (retenciones)', 'fa-calculator', false],
    ];

    public function index(Request $request)
    {
        $sel = $request->query('sistema');
        $sel = isset(self::SISTEMAS[$sel]) && self::SISTEMAS[$sel][2] ? $sel : null;
        $roles = collect(RolesNegocio::plantillasPorDefecto('restaurante'))->map(function ($k) {
            [$nombre, $icono, $perm, $inicio, $estacion] = RolesNegocio::PLANTILLAS[$k];
            return ['nombre' => $nombre, 'icono' => $icono, 'permisos' => $perm, 'inicio' => \App\Models\NegocioRol::INICIOS[$inicio] ?? $inicio, 'estacion' => $estacion];
        });
        $textos = collect(RolesNegocio::BASE)->collapse();
        return view('admin.pruebas', [
            'sistemas' => self::SISTEMAS, 'sel' => $sel, 'ciclos' => PruebaRestaurante::CICLOS,
            'roles' => $roles, 'textos' => $textos, 'resultados' => session('resultados'),
            'qa' => RecorridoRestaurante::tienda(), 'qaUsuarios' => RecorridoRestaurante::usuarios(),
        ]);
    }

    public function ejecutar(Request $request)
    {
        @set_time_limit(120);
        $modos = $request->input('modo') === 'ambos' ? ['antes', 'despues'] : [$request->input('modo') === 'despues' ? 'despues' : 'antes'];
        $res = [];
        foreach ($modos as $m) $res[] = (new PruebaRestaurante())->ejecutar($m);
        return redirect()->route('admin.pruebas', ['sistema' => 'restaurante'])->with('resultados', $res);
    }

    public function recorrido()
    {
        $clave = RecorridoRestaurante::preparar();
        return redirect()->route('admin.pruebas', ['sistema' => 'restaurante'])
            ->with('success', "Restaurante de prueba listo. Clave de todos los usuarios: $clave (anótela, no se vuelve a mostrar).");
    }
}
