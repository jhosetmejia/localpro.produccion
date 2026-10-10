<?php

namespace App\Models\Concerns;

use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aísla los datos por tienda: cada consulta solo ve los registros de la tienda actual
 * y cada registro nuevo queda guardado en esa tienda.
 */
trait PerteneceATienda
{
    public static function bootPerteneceATienda(): void
    {
        static::addGlobalScope('tienda', function (Builder $q) {
            if ($id = TiendaActual::id()) {
                $q->where($q->getModel()->getTable() . '.tienda_id', $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tienda_id) && TiendaActual::id()) {
                $model->tienda_id = TiendaActual::id();
            }
        });
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }
}
