<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\TiendaActual;
use Illuminate\Http\Request;

// El dueño / administrador le pone metas de venta (del día y del mes) a cada vendedor
class metaController extends Controller
{
    public function guardar(Request $request, User $user)
    {
        $yo = $request->user();
        abort_unless($yo->es_superadmin || $yo->hasAnyRole(['dueno', 'administrador']), 403);
        abort_unless((int) $user->tienda_id === (int) TiendaActual::id(), 404);
        $d = $request->validate([
            'meta_dia' => 'nullable|numeric|min:0|max:9999999999',
            'meta_mes' => 'nullable|numeric|min:0|max:9999999999',
        ]);
        $user->forceFill([
            'meta_dia' => ($d['meta_dia'] ?? 0) > 0 ? $d['meta_dia'] : null,
            'meta_mes' => ($d['meta_mes'] ?? 0) > 0 ? $d['meta_mes'] : null,
        ])->save();
        return back()->with('success', 'Meta de ' . strtok($user->name, ' ') . ' guardada.');
    }
}
