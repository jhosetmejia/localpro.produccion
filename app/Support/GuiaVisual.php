<?php

namespace App\Support;

use App\Models\Tienda;

/**
 * Guías visuales de los cursos y el blog: dibujan el menú real de LocalPro y el camino de clics
 * ("Menú → Eventos y entradas → Editar → Formulario de registro").
 * Los nombres e íconos salen de la misma lista del menú (Tienda::MENU_OPCIONAL), así que si una
 * sección cambia de nombre, de ícono o de orden, la guía se actualiza sola.
 */
class GuiaVisual
{
    // Secciones fijas del menú (siempre visibles para el dueño)
    public const FIJOS = [
        'panel' => ['Panel', 'fa-gauge-high'],
        'negocio' => ['Datos del negocio', 'fa-store'],
        'usuarios' => ['Usuarios', 'fa-user-gear'],
        'roles' => ['Roles del negocio', 'fa-id-badge'],
        'menu' => ['Personalizar menú', 'fa-sliders'],
        'aprende' => ['Aprende', 'fa-graduation-cap'],
    ];

    // Secciones que dependen de un módulo o tipo de negocio: [módulo de la tienda, tipos que la tienen]
    private const DE_MODULO = [
        'consultorio' => ['mod_consultorio', ['consultorio']],
        'obituarios' => ['mod_obituarios', ['funeraria']],
        'eventos' => ['mod_eventos', ['eventos']],
        'contabilidad' => ['mod_contable', ['contable']],
        'pedidos' => ['mod_pedidos', ['restaurante', 'cafeteria']],
        'alquiler' => ['mod_alquiler', []],
        'tiquetera' => ['usa_tiquetera', []],
    ];

    public static function item(string $clave): array
    {
        if (isset(self::FIJOS[$clave])) return ['clave' => $clave, 'nombre' => self::FIJOS[$clave][0], 'icono' => self::FIJOS[$clave][1]];
        $m = Tienda::MENU_OPCIONAL[$clave] ?? null;
        return $m ? ['clave' => $clave, 'nombre' => $m[0], 'icono' => $m[1]] : ['clave' => $clave, 'nombre' => $clave, 'icono' => 'fa-circle'];
    }

    /**
     * Menú lateral como lo vería el negocio: el de la tienda actual (con lo que tenga oculto),
     * o el de un tipo de negocio (para el blog y los asesores). Siempre incluye la sección destino.
     */
    public static function menu(?string $tipo, string $destino, int $max = 9): array
    {
        $tienda = $tipo ? null : TiendaActual::tienda();
        $tipo ??= $tienda?->tipo_negocio;
        $ocultos = $tienda && $tienda->menu_oculto !== null ? (array) $tienda->menu_oculto : (Tienda::MENU_OCULTO_POR_TIPO[$tipo] ?? []);

        $opcionales = [];
        foreach (array_keys(Tienda::MENU_OPCIONAL) as $k) {
            if ($k === $destino) { $opcionales[] = $k; continue; }
            if (in_array($k, $ocultos, true)) continue;
            if (isset(self::DE_MODULO[$k])) {
                [$mod, $tipos] = self::DE_MODULO[$k];
                $tiene = $tienda ? (bool) $tienda->{$mod} : in_array($tipo, $tipos, true);
                if (!$tiene) continue;
            }
            $opcionales[] = $k;
        }
        // Se recorta para que quepa, sin perder el destino
        $antes = array_slice($opcionales, 0, $max);
        if (!in_array($destino, $antes, true) && isset(Tienda::MENU_OPCIONAL[$destino])) {
            $antes = array_merge(array_slice($antes, 0, $max - 1), [$destino]);
        }
        $claves = array_merge(['panel'], $antes, ['negocio', 'usuarios', 'roles', 'menu', 'aprende']);
        return array_map(fn ($k) => self::item($k) + ['activo' => $k === $destino], array_values(array_unique($claves)));
    }
}
