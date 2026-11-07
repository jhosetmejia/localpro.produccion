<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Campanita de notificaciones (como Facebook) arriba en el panel.
 *
 * Para agregar un tipo nuevo: cree una clase con `public static function notificaciones(User $u): array`
 * y agréguela en FUENTES. Cada notificación es un arreglo:
 *   clave      string  único (ej. 'separado-15')
 *   icono      string  ícono Font Awesome (ej. 'fa-bag-shopping')
 *   color      string  azul | verde | amarillo | naranja | rojo | morado
 *   prioridad  int     1 = más urgente (sale primero)
 *   texto      string  HTML corto (escape los datos con e())
 *   cuando     string  ej. 'Vence mañana', 'Hace 2 horas'
 *   fecha      string  para ordenar (Y-m-d o Y-m-d H:i)
 *   url        ?string a dónde lleva al tocarla
 *   wa         ?string enlace de WhatsApp opcional (botón verde)
 */
class Notificaciones
{
    public const FUENTES = [
        Anuncios::class,
        SeparadosPorVencer::class,
    ];

    public static function paraUsuario(?User $u): array
    {
        if (!$u) return [];
        try {
            return Cache::remember(self::clave($u), 120, function () use ($u) {
                $todas = [];
                foreach (self::FUENTES as $f) {
                    try {
                        foreach ($f::notificaciones($u) as $n) $todas[] = $n + ['color' => 'azul', 'prioridad' => 5, 'cuando' => '', 'fecha' => '', 'url' => null, 'wa' => null, 'wa_titulo' => 'WhatsApp'];
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
                usort($todas, fn ($a, $b) => [$a['prioridad'], $a['fecha']] <=> [$b['prioridad'], $b['fecha']]);
                return array_slice($todas, 0, 50);
            });
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    // Número del globito: solo las que no ha visto desde la última vez que abrió la campanita
    public static function sinVer(User $u, array $lista): int
    {
        $vistas = (array) Cache::get('noti_vistas:' . $u->id, []);
        return count(array_filter($lista, fn ($n) => !in_array($n['clave'], $vistas, true)));
    }

    public static function marcarVistas(User $u): void
    {
        Cache::put('noti_vistas:' . $u->id, array_column(self::paraUsuario($u), 'clave'), now()->addDays(60));
    }

    public static function olvidar(User $u): void
    {
        Cache::forget(self::clave($u));
    }

    private static function clave(User $u): string
    {
        return 'notificaciones:' . $u->id . ':' . (TiendaActual::id() ?: 0);
    }
}
