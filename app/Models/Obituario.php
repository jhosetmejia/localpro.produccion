<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

// Obituario de un fallecido que se muestra en la pantalla de la sala de velación
class Obituario extends Model
{
    use PerteneceATienda;

    protected $table = 'obituarios';

    protected $fillable = ['plantilla_id', 'sala_id', 'nombre', 'nacimiento', 'fallecimiento', 'frase', 'fecha_exequias', 'hora_exequias', 'lugar_exequias', 'destino_final', 'invitan', 'mostrar_hasta', 'estado'];

    protected $casts = ['nacimiento' => 'date', 'fallecimiento' => 'date', 'fecha_exequias' => 'date', 'mostrar_hasta' => 'datetime'];

    public function plantilla()
    {
        return $this->belongsTo(ObituarioPlantilla::class, 'plantilla_id');
    }

    public function sala()
    {
        return $this->belongsTo(ObituarioSala::class, 'sala_id');
    }

    // Activos y sin pasar la hora en que deben salir de la pantalla
    public function scopeEnPantalla($q)
    {
        return $q->where('estado', 'activo')->where(fn ($w) => $w->whereNull('mostrar_hasta')->orWhere('mostrar_hasta', '>', now()));
    }

    public function getEnPantallaAttribute(): bool
    {
        return $this->estado === 'activo' && (!$this->mostrar_hasta || $this->mostrar_hasta->isFuture());
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null;
    }

    // "Martes 13 de octubre de 2026"
    public function getFechaTextoAttribute(): ?string
    {
        return $this->fecha_exequias ? self::fechaLarga($this->fecha_exequias) : null;
    }

    public static function fechaLarga(Carbon $f): string
    {
        return \Illuminate\Support\Str::ucfirst($f->copy()->locale('es')->translatedFormat('l j \d\e F \d\e Y'));
    }

    // "3 p. m." / "3:30 p. m."
    public function getHoraTextoAttribute(): ?string
    {
        return self::horaTexto($this->hora_exequias);
    }

    public static function horaTexto(?string $hora): ?string
    {
        if (!$hora) return null;
        $h = Carbon::createFromFormat(strlen($hora) > 5 ? 'H:i:s' : 'H:i', $hora);
        return $h->format($h->minute ? 'g:i' : 'g') . ($h->hour < 12 ? ' a. m.' : ' p. m.');
    }

    // "1958 – 2026"
    public function getAniosAttribute(): ?string
    {
        if (!$this->nacimiento && !$this->fallecimiento) return null;
        return trim(($this->nacimiento?->year ?? '') . ' – ' . ($this->fallecimiento?->year ?? ''), ' –');
    }

    // "Su madre: Rosa Rodríguez" → [['Su madre', 'Rosa Rodríguez'], …]
    public static function grupos(?string $texto): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $texto) as $linea) {
            $linea = trim($linea);
            if ($linea === '') continue;
            if (str_contains($linea, ':')) {
                [$t, $n] = array_map('trim', explode(':', $linea, 2));
                $out[] = [$t, $n];
            } else {
                $out[] = [$linea, ''];
            }
        }
        return $out;
    }

    public const INVITAN_EJEMPLO = "Su esposa: \nSus hijos: \nSus hermanos: \nSus nietos\nDemás familiares y amigos";
}
