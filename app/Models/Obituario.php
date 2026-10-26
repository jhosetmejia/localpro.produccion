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

    protected $fillable = ['plantilla_id', 'sala_id', 'lugar_velacion', 'nombre', 'nacimiento', 'fallecimiento', 'frase', 'fecha_exequias', 'hora_exequias', 'lugar_exequias', 'destino_final', 'invitan', 'mostrar_hasta', 'estado', 'foto_fondo', 'foto_color', 'foto_forma', 'foto_rot', 'foto_x', 'foto_y', 'foto_zoom'];

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

    // "07-12-1980 / 27-03-2026" (fecha completa de nacimiento y fallecimiento)
    public function getAniosAttribute(): ?string
    {
        if (!$this->nacimiento && !$this->fallecimiento) return null;
        return trim(($this->nacimiento?->format('d-m-Y') ?? '') . ' / ' . ($this->fallecimiento?->format('d-m-Y') ?? ''), ' /');
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

    // Detrás de la foto: [nombre, ayuda]
    public const FOTO_FONDOS = [
        'suave' => ['Fondo suave', 'Un fondo claro detrás de la persona'],
        'sin' => ['Sin fondo', 'Se ve la tarjeta detrás (para fotos PNG sin fondo)'],
        'color' => ['Un color', 'Elija el color'],
    ];
    public const FOTO_FORMAS = ['circulo' => 'En círculo', 'libre' => 'Recorte libre (sin marco)'];

    // Clases y estilo del recuadro de la foto en el lienzo
    public static function fotoEstilo(?self $o): array
    {
        $fondo = $o->foto_fondo ?? 'suave';
        $forma = $o->foto_forma ?? 'circulo';
        $color = $fondo === 'color' && $o?->foto_color ? $o->foto_color : null;
        // Ajuste fino: giro en grados, movimiento en % y tamaño en % (variables que usa la foto en el CSS)
        $aj = self::ajusteFoto($o);
        $vars = "--pr:{$aj['r']}deg;--px:{$aj['x']}%;--py:{$aj['y']}%;--pz:" . ($aj['z'] / 100);
        return ['clase' => "f-$fondo forma-$forma", 'estilo' => ($color ? "background:$color;" : '') . $vars];
    }

    public static function ajusteFoto(?self $o): array
    {
        return [
            'r' => max(-180, min(180, (int) ($o?->foto_rot ?? 0))),
            'x' => max(-100, min(100, (int) ($o?->foto_x ?? 0))),
            'y' => max(-100, min(100, (int) ($o?->foto_y ?? 0))),
            'z' => max(50, min(300, (int) ($o?->foto_zoom ?: 100))),
        ];
    }

    public const INVITAN_EJEMPLO = "Su esposa: \nSus hijos: \nSus hermanos: \nSus nietos\nDemás familiares y amigos";
}
