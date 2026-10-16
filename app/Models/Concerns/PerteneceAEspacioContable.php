<?php

namespace App\Models\Concerns;

use App\Models\Asesor;
use App\Models\Tienda;
use App\Support\EspacioContable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Como PerteneceATienda, pero el dueño puede ser un negocio o un asesor de la red (ver EspacioContable).
 */
trait PerteneceAEspacioContable
{
    public static function bootPerteneceAEspacioContable(): void
    {
        static::addGlobalScope('espacio_contable', function (Builder $q) {
            if ($d = EspacioContable::dueno()) {
                $q->where($q->getModel()->getTable() . '.' . $d[0], $d[1]);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tienda_id) && empty($model->asesor_id) && ($d = EspacioContable::dueno())) {
                $model->{$d[0]} = $d[1];
            }
        });
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function asesor()
    {
        return $this->belongsTo(Asesor::class);
    }
}
