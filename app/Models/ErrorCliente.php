<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Errores de JavaScript que les salen a las personas en el navegador (LocalProCut, panel…).
 * Se agrupan por firma: el mismo error suma "veces" en vez de crear filas nuevas.
 */
class ErrorCliente extends Model
{
    protected $table = 'errores_cliente';

    protected $fillable = ['firma', 'modulo', 'mensaje', 'donde', 'pila', 'pagina', 'navegador', 'datos', 'veces', 'usuarios', 'resuelto', 'primera_vez', 'ultima_vez'];

    protected $casts = ['datos' => 'array', 'resuelto' => 'boolean', 'primera_vez' => 'datetime', 'ultima_vez' => 'datetime'];
}
