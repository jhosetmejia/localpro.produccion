<?php

namespace App\Support;

use App\Models\NegocioRol;
use App\Models\PedidoEstacion;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Roles propios de cada negocio. Cada empleado con rol del negocio tiene el rol base "empleado"
 * (solo su perfil) y recibe como permisos directos los que el dueño marcó en su rol.
 * Al cambiar los permisos de un rol, se actualizan todas las personas que lo tienen.
 */
class RolesNegocio
{
    public const BASE = [
        'Caja y ventas' => [
            'usar-pos' => 'Vender en la caja (POS) y abrir/cerrar su caja',
            'ver-producto' => 'Ver productos y precios',
            'ver-inventario' => 'Ver existencias',
            'ver-cliente' => 'Ver clientes',
            'crear-cliente' => 'Crear clientes',
        ],
        'Pedidos (restaurante)' => [
            'pedidos-tomar' => 'Tomar pedidos (mesero): anota lo que pide el cliente y lo entrega',
            'pedidos-cobrar' => 'Cobrar pedidos en la caja',
            'pedidos-preparar' => 'Pantalla de estación: ve lo que le toca preparar o atender y lo marca listo',
            'pedidos-administrar' => 'Configurar estaciones y ver todos los pedidos y el cierre',
        ],
        'Eventos y entradas' => [
            'gestionar-eventos' => 'Administrar eventos: crear, borrar, precio, datos de pago y publicar (todo)',
            'eventos-pagos' => 'Contabilidad: ver compradores, aprobar o rechazar pagos y descargar el Excel',
            'eventos-informacion' => 'Logística: editar la información del evento (fecha, lugar, descripción, condiciones, redes) y ver asistentes',
            'eventos-diseno' => 'Diseño: cambiar afiches/banner, logo, estilo y color de la página del evento',
            'eventos-ingreso' => 'Puerta del evento: escanear el QR y dejar entrar',
        ],
    ];

    // Lo que siempre necesita otro permiso para funcionar
    private const REQUIERE = [
        'pedidos-cobrar' => 'usar-pos',
        'crear-categoria' => 'ver-categoria', 'editar-categoria' => 'ver-categoria', 'eliminar-categoria' => 'ver-categoria',
        'crear-proveedore' => 'ver-proveedore', 'editar-proveedore' => 'ver-proveedore', 'eliminar-proveedore' => 'ver-proveedore',
        'crear-entradas' => 'ver-entradas', 'crear-metodo-pago' => 'ver-metodo-pago', 'editar-metodo-pago' => 'ver-metodo-pago',
    ];

    // Plantillas listas: clave => [nombre, ícono, permisos, inicio, estación que crea/usa]
    public const PLANTILLAS = [
        'caja' => ['Caja', 'fa-cash-register', ['usar-pos', 'ver-producto', 'ver-cliente', 'crear-cliente', 'pedidos-cobrar'], 'caja', null],
        'mesero' => ['Mesero', 'fa-bell-concierge', ['pedidos-tomar', 'ver-producto'], 'mesero', null],
        'cocina' => ['Cocina', 'fa-fire-burner', ['pedidos-preparar'], 'estacion', 'Cocina'],
        'cafeteria' => ['Cafetería', 'fa-mug-hot', ['pedidos-preparar'], 'estacion', 'Cafetería'],
        'bar' => ['Bar y bebidas', 'fa-martini-glass', ['pedidos-preparar'], 'estacion', 'Bar y bebidas'],
        'entradas' => ['Entradas', 'fa-ticket', ['pedidos-preparar'], 'estacion', 'Entradas'],
        'paseos' => ['Paseos con animales', 'fa-horse', ['pedidos-preparar'], 'estacion', 'Paseos con animales'],
        'granjero' => ['Granjero', 'fa-tractor', ['pedidos-preparar'], 'estacion', 'Granja'],
        'puerta' => ['Puerta (eventos)', 'fa-qrcode', ['eventos-ingreso'], 'eventos', null],
        'ev_admin' => ['Administrador del evento', 'fa-user-tie', ['gestionar-eventos', 'eventos-pagos', 'eventos-informacion', 'eventos-diseno', 'eventos-ingreso'], 'eventos', null],
        'ev_contabilidad' => ['Contabilidad', 'fa-calculator', ['eventos-pagos'], 'eventos', null],
        'ev_logistica' => ['Logística', 'fa-truck-ramp-box', ['eventos-informacion', 'eventos-ingreso'], 'eventos', null],
        'ev_diseno' => ['Diseño', 'fa-palette', ['eventos-diseno'], 'eventos', null],
        'admin' => ['Administrador', 'fa-user-tie', ['*'], 'panel', null],
    ];

    // Roles que ya quedan creados al abrir un negocio con pedidos (el dueño no tiene que inventarlos)
    public const POR_DEFECTO = [
        'restaurante' => ['admin', 'caja', 'mesero', 'cocina', 'bar'],
        'cafeteria' => ['admin', 'caja', 'mesero', 'cafeteria'],
        'otro' => ['caja', 'mesero', 'cocina'],
    ];

    // Roles que quedan creados al encender Eventos (el empresario no tiene que inventarlos)
    public const EVENTOS_POR_DEFECTO = ['ev_admin', 'ev_contabilidad', 'ev_logistica', 'ev_diseno', 'puerta'];

    /**
     * Crea los roles de Eventos que falten (por nombre). No toca los que el negocio ya tenga ni repite los existentes.
     * Devuelve cuántos creó.
     */
    public static function crearEventosPorDefecto(\App\Models\Tienda $tienda): int
    {
        if (!$tienda->mod_eventos) return 0;
        return (int) TiendaActual::como($tienda->id, function () {
            $n = 0;
            foreach (self::EVENTOS_POR_DEFECTO as $clave) {
                if (NegocioRol::where('nombre', self::PLANTILLAS[$clave][0])->exists()) continue;
                self::desdePlantilla($clave);
                $n++;
            }
            return $n;
        });
    }

    public static function plantillasPorDefecto(?string $tipo): array
    {
        return self::POR_DEFECTO[$tipo] ?? self::POR_DEFECTO['otro'];
    }

    /**
     * Crea los roles por defecto (con sus permisos y estaciones) si el negocio tiene pedidos y aún no tiene roles.
     * Devuelve cuántos creó.
     */
    public static function crearPorDefecto(\App\Models\Tienda $tienda): int
    {
        if (!$tienda->mod_pedidos) return 0;
        return (int) TiendaActual::como($tienda->id, function () use ($tienda) {
            if (NegocioRol::exists()) return 0;
            PedidoEstacion::asegurar();
            $n = 0;
            foreach (self::plantillasPorDefecto($tienda->tipo_negocio) as $clave) {
                self::desdePlantilla($clave);
                $n++;
            }
            return $n;
        });
    }

    // [grupo => [permiso => texto]] con lo que este negocio puede dar
    public static function disponibles(): array
    {
        $t = TiendaActual::tienda();
        $existen = Permission::pluck('name')->all();
        $out = [];
        foreach (self::BASE as $g => $ps) {
            foreach ($ps as $p => $txt) {
                if (!in_array($p, $existen, true)) continue;
                if (str_starts_with($p, 'pedidos-') && $t && !$t->mod_pedidos) continue;
                if (($p === 'gestionar-eventos' || str_starts_with($p, 'eventos-')) && $t && !$t->mod_eventos) continue;
                $out[$g][$p] = $txt;
            }
        }
        foreach (PermisosVendedor::GRUPOS as $g => $ps) {
            foreach ($ps as $p => $txt) {
                if (in_array($p, $existen, true) && !in_array($p, PermisosVendedor::PROHIBIDOS, true) && PermisosVendedor::aplica($p)) {
                    $out[$g][$p] = $txt;
                }
            }
        }
        return $out;
    }

    public static function nombres(): array
    {
        return collect(self::disponibles())->flatMap(fn ($g) => array_keys($g))->values()->all();
    }

    // Deja solo permisos válidos y agrega los que hacen falta para que funcionen
    public static function limpiar(array $elegidos): array
    {
        $validos = self::nombres();
        if (in_array('*', $elegidos, true)) return $validos;
        foreach ($elegidos as $p) {
            if (isset(self::REQUIERE[$p])) $elegidos[] = self::REQUIERE[$p];
        }
        return array_values(array_unique(array_intersect($elegidos, $validos)));
    }

    // Le pone a la persona el rol del negocio (o se lo quita con null)
    public static function asignar(User $u, ?NegocioRol $rol): void
    {
        if ($u->es_superadmin) return;
        if ($rol) {
            $u->forceFill(['negocio_rol_id' => $rol->id])->save();
            Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web']);
            $u->syncRoles(['empleado']);
            $u->syncPermissions(self::limpiar((array) $rol->permisos));
        } else {
            $u->forceFill(['negocio_rol_id' => null])->save();
        }
    }

    // Después de cambiar un rol: todas sus personas quedan con los permisos nuevos
    public static function sincronizar(NegocioRol $rol): void
    {
        $perm = self::limpiar((array) $rol->permisos);
        foreach ($rol->users()->get() as $u) {
            if ($u->es_superadmin) continue;
            $u->syncRoles(['empleado']);
            $u->syncPermissions($perm);
        }
    }

    // Crea un rol desde una plantilla (y su estación si hace falta)
    public static function desdePlantilla(string $clave): NegocioRol
    {
        [$nombre, $icono, $perm, $inicio, $estacion] = self::PLANTILLAS[$clave];
        $estIds = [];
        if ($estacion) {
            $e = PedidoEstacion::where('nombre', $estacion)->first()
                ?? PedidoEstacion::create(['nombre' => $estacion, 'icono' => $icono, 'orden' => (int) PedidoEstacion::max('orden') + 1]);
            $estIds = [$e->id];
        }
        $base = $nombre;
        $i = 2;
        while (NegocioRol::where('nombre', $nombre)->exists()) $nombre = $base . ' ' . $i++;
        return NegocioRol::create([
            'nombre' => $nombre, 'icono' => $icono, 'permisos' => self::limpiar($perm), 'estaciones' => $estIds,
            'inicio' => $inicio, 'orden' => (int) NegocioRol::max('orden') + 1,
        ]);
    }

    // Etiqueta del rol de una persona: "Mesero", "Dueño / administrador" o "Vendedor (caja)"
    public static function etiqueta(User $u): string
    {
        if ($u->es_superadmin) return 'Administrador LocalPro';
        if ($u->negocio_rol_id && ($r = NegocioRol::find($u->negocio_rol_id))) return $r->nombre;
        if ($u->hasRole('dueno')) return 'Dueño / administrador';
        if ($u->hasRole('vendedor')) return 'Vendedor (caja)';
        return 'Sin rol';
    }
}
