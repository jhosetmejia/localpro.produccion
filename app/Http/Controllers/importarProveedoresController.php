<?php

namespace App\Http\Controllers;

// Subir un Excel, CSV u hoja de Google de proveedores: usa el mismo importador inteligente de clientes
class importarProveedoresController extends importarClientesController
{
    public function __construct()
    {
        $this->middleware('permission:crear-proveedore');
    }

    protected function cfg(): array
    {
        return [
            'tipo' => 'proveedores', 'relacion' => 'proveedore', 'ruta' => 'proveedores', 'icono' => 'fa-truck-field',
            'singular' => 'proveedor', 'plural' => 'proveedores', 'Plural' => 'Proveedores', 'volver' => 'Mis proveedores',
            'quien' => 'proveedores', 'ejemplo' => 'el Excel de su sistema anterior, la lista de su contador, sus contactos de compras…',
            'listo' => 'En un instante quedan listos para registrarles compras.',
        ];
    }
}
