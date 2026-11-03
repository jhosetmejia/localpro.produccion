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

    protected $fillable = ['paciente_id', 'profesional_id', 'fecha', 'hora', 'duracion', 'motivo', 'estado', 'valor', 'nota', 'atencion_id', 'user_id', 'origen', 'token', 'recordada_at', 'confirmada_at'];

    protected $casts = ['fecha' => 'date', 'valor' => 'decimal:2', 'recordada_at' => 'datetime', 'confirmada_at' => 'datetime'];

    // Enlace para que el paciente confirme o cancele su cita sin entrar al sistema
    public function getUrlConfirmarAttribute(): string
    {
        if (!$this->token) $this->forceFill(['token' => \Illuminate\Support\Str::random(40)])->save();
        return route('citas.confirmar', $this->token);
    }

    public function mensajeRecordatorio(?Tienda $t = null): string
    {
        $t ??= \App\Support\TiendaActual::tienda();
        return 'Hola ' . $this->paciente?->nombres . ', le recordamos su cita en ' . $t?->nombre . ' el ' . $this->fecha->translatedFormat('l j \d\e F')
            . ' a las ' . $this->hora_corta . '. Confirme o cancele aquí: ' . $this->url_confirmar . ' ¡Gracias!';
    }

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
