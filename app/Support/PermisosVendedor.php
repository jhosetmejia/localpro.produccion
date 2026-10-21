<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Permisos extra que el dueño de la tienda le puede dar a un vendedor (además de los de caja).
// Solo se ofrecen los que el rol "dueno" tiene y el vendedor aún no tiene por su rol.
// Nunca se ofrecen los de administración (usuarios, datos del negocio, plan, roles).
class PermisosVendedor
{
    public const GRUPOS = [
        'Productos' => [
            'crear-producto' => 'Crear productos',
            'editar-producto' => 'Editar productos (precios, fotos, datos)',
            'eliminar-producto' => 'Eliminar productos',
        ],
        'Inventario' => [
            'ver-entradas' => 'Ver entradas de mercancía (ve el costo)',
            'crear-entradas' => 'Registrar entradas de mercancía (ve el costo)',
            'ajustar-inventario' => 'Ajustar existencias',
        ],
        'Categorías' => [
            'ver-categoria' => 'Ver categorías',
            'crear-categoria' => 'Crear categorías',
            'editar-categoria' => 'Editar categorías',
            'eliminar-categoria' => 'Eliminar categorías',
        ],
        'Clientes' => [
            'editar-cliente' => 'Editar clientes',
            'eliminar-cliente' => 'Eliminar clientes',
        ],
        'Proveedores' => [
            'ver-proveedore' => 'Ver proveedores',
            'crear-proveedore' => 'Crear proveedores',
            'editar-proveedore' => 'Editar proveedores',
            'eliminar-proveedore' => 'Eliminar proveedores',
        ],
        'Ventas y caja' => [
            'anular-venta-pos' => 'Anular ventas',
            'devolver-venta' => 'Hacer devoluciones',
            'ver-cajas' => 'Ver cajas',
            'ver-cajas-todas' => 'Ver cierres de caja de todos',
            'ver-reportes' => 'Ver reportes (incluye ganancias)',
            'ver-egresos-todos' => 'Ver los egresos (gastos) de todos',
        ],
        'Mis enlaces' => [
            'gestionar-enlaces' => 'Crear y editar Mis enlaces (la página de enlaces del negocio para Instagram, TikTok y WhatsApp)',
        ],
        'Consultorio' => [
            'historia-clinica' => 'Historia clínica (profesional de salud: ve y escribe historias)',
        ],
        'Otros' => [
            'ver-metodo-pago' => 'Ver métodos de pago',
            'crear-metodo-pago' => 'Crear métodos de pago',
            'editar-metodo-pago' => 'Editar métodos de pago',
            'publicar-redes' => 'Publicar en redes sociales',
            'facturacion-dian' => 'Facturación electrónica DIAN',
            'gestionar-nomina' => 'Nómina',
            'contabilidad' => 'Contabilidad',
        ],
    ];

    // Nunca se le delegan a un vendedor
    public const PROHIBIDOS = [
        'ver-user', 'crear-user', 'editar-user', 'eliminar-user', 'editar-negocio',
        'ver-role', 'crear-role', 'editar-role', 'eliminar-role',
    ];

    // Permisos que solo se ofrecen si la tienda tiene ese módulo (una tienda de ropa no ve "Historia clínica")
    public static function aplica(string $p): bool
    {
        $t = TiendaActual::tienda();
        if (!$t) return true;
        return match ($p) {
            'historia-clinica', 'agenda-consultorio' => (bool) $t->mod_consultorio,
            'contabilidad' => (bool) $t->mod_contable,
            'facturacion-dian' => !$t->pais || $t->pais === 'CO',
            default => true,
        };
    }

    // [grupo => [permiso => etiqueta]] solo con lo que se puede dar
    public static function disponibles(): array
    {
        $dueno = Role::where('name', 'dueno')->first();
        $deDueno = $dueno ? $dueno->permissions->pluck('name')->all() : [];
        $vendedor = Role::where('name', 'vendedor')->first();
        $yaTiene = $vendedor ? $vendedor->permissions->pluck('name')->all() : [];
        $existen = Permission::pluck('name')->all();

        $ok = fn ($p) => in_array($p, $existen, true) && in_array($p, $deDueno, true)
            && !in_array($p, $yaTiene, true) && !in_array($p, self::PROHIBIDOS, true) && self::aplica($p);

        $out = [];
        $listados = [];
        foreach (self::GRUPOS as $grupo => $permisos) {
            foreach ($permisos as $p => $txt) {
                $listados[] = $p;
                if ($ok($p)) $out[$grupo][$p] = $txt;
            }
        }
        // Permisos nuevos del dueño que aún no estén en la lista: se muestran igual
        foreach ($deDueno as $p) {
            if (!in_array($p, $listados, true) && $ok($p)) {
                $out['Otros'][$p] = ucfirst(str_replace('-', ' ', $p));
            }
        }
        return $out;
    }

    public static function nombres(): array
    {
        return collect(self::disponibles())->flatMap(fn ($g) => array_keys($g))->values()->all();
    }

    // Quien puede crear/editar algo también necesita poder ver la lista
    public static function completar(array $elegidos): array
    {
        $requiere = [
            'crear-categoria' => 'ver-categoria', 'editar-categoria' => 'ver-categoria', 'eliminar-categoria' => 'ver-categoria',
            'crear-proveedore' => 'ver-proveedore', 'editar-proveedore' => 'ver-proveedore', 'eliminar-proveedore' => 'ver-proveedore',
            'crear-entradas' => 'ver-entradas',
            'crear-metodo-pago' => 'ver-metodo-pago', 'editar-metodo-pago' => 'ver-metodo-pago',
        ];
        $validos = self::nombres();
        foreach ($elegidos as $p) {
            if (isset($requiere[$p]) && in_array($requiere[$p], $validos, true)) $elegidos[] = $requiere[$p];
        }
        return array_values(array_unique(array_intersect($elegidos, $validos)));
    }
}
