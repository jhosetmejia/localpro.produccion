<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Odontograma del paciente. Cada vez que se guarda queda una versión nueva (no se reescribe la anterior).
class Odontograma extends Model
{
    use PerteneceATienda;

    protected $fillable = ['paciente_id', 'atencion_id', 'user_id', 'profesional_nombre', 'denticion', 'piezas', 'notas'];

    protected $casts = ['piezas' => 'encrypted:array', 'notas' => 'encrypted'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function atencion()
    {
        return $this->belongsTo(Atencion::class);
    }
}
