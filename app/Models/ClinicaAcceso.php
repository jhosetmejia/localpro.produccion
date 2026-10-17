<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Registro de quién vio o tocó cada historia clínica
class ClinicaAcceso extends Model
{
    use PerteneceATienda;

    protected $table = 'clinica_accesos';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'paciente_id', 'atencion_id', 'accion', 'ip'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function anotar(string $accion, ?int $pacienteId, ?int $atencionId = null): void
    {
        static::create(['user_id' => auth()->id(), 'paciente_id' => $pacienteId, 'atencion_id' => $atencionId, 'accion' => $accion, 'ip' => request()->ip()]);
    }
}
