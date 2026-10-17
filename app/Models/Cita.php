<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use PerteneceATienda;

    public const ESTADOS = [
        'programada' => ['Programada', '#64748b'],
        'confirmada' => ['Confirmada', '#0ea5e9'],
        'atendida' => ['Atendida', '#16a34a'],
        'no_asistio' => ['No asistió', '#e11d48'],
        'cancelada' => ['Cancelada', '#94a3b8'],
    ];

    protected $fillable = ['paciente_id', 'profesional_id', 'fecha', 'hora', 'duracion', 'motivo', 'estado', 'valor', 'nota', 'atencion_id', 'user_id'];

    protected $casts = ['fecha' => 'date', 'valor' => 'decimal:2'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profesional()
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    public function atencion()
    {
        return $this->belongsTo(Atencion::class);
    }

    public function getHoraCortaAttribute(): string
    {
        return \Carbon\Carbon::parse($this->hora)->format('h:i a');
    }

    public function getActivaAttribute(): bool
    {
        return in_array($this->estado, ['programada', 'confirmada'], true);
    }
}
