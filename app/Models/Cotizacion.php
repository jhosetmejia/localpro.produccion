<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

// Cotización: productos o líneas libres con su precio (se puede cambiar), PDF con el logo y enlace para el cliente
class Cotizacion extends Model
{
    use PerteneceATienda;

    protected $table = 'cotizaciones';

    protected $fillable = ['numero', 'cliente_id', 'cliente_nombre', 'cliente_documento', 'cliente_telefono', 'cliente_email', 'cliente_direccion',
        'asunto', 'evento_fecha', 'evento_hasta', 'evento_hora', 'evento_hora_fin', 'evento_lugar', 'alquiler_id', 'fecha', 'vence', 'subtotal', 'descuento', 'total', 'condiciones', 'logo_path', 'estado', 'user_id'];

    protected $casts = ['fecha' => 'date', 'vence' => 'date', 'evento_fecha' => 'date', 'evento_hasta' => 'date', 'subtotal' => 'float', 'descuento' => 'float', 'total' => 'float'];

    public const ESTADOS = [
        'pendiente' => ['Pendiente', '#8a6400', '#fff5d6'],
        'aceptada' => ['Aceptada', '#11702f', '#e3f9e8'],
        'rechazada' => ['No aceptada', '#9b1c1c', '#fde8e8'],
    ];

    // Condiciones de la primera cotización (después se usan las de la última que hizo el negocio)
    public const CONDICIONES = "Forma de pago: 50% para separar y 50% el día de la entrega.\nCotización sujeta a disponibilidad en la fecha de su evento.";

    public function items() { return $this->hasMany(CotizacionItem::class)->orderBy('orden'); }
    public function user() { return $this->belongsTo(User::class); }
    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function alquiler() { return $this->belongsTo(Alquiler::class); }

    // "15/11/2026" o "15/11/2026 al 17/11/2026"
    public function getEventoTextoAttribute(): ?string
    {
        if (!$this->evento_fecha) return null;
        return $this->evento_fecha->format('d/m/Y') . ($this->evento_hasta && $this->evento_hasta->gt($this->evento_fecha) ? ' al ' . $this->evento_hasta->format('d/m/Y') : '');
    }

    // "7:00 p. m." o "7:00 p. m. a 1:00 a. m." (la hora es opcional)
    public function getHorarioTextoAttribute(): ?string
    {
        $f = fn ($h) => $h ? str_replace(['AM', 'PM'], ['a. m.', 'p. m.'], \Carbon\Carbon::parse($h)->format('g:i A')) : null;
        $ini = $f($this->evento_hora);
        $fin = $f($this->evento_hora_fin);
        if (!$ini && !$fin) return null;
        return $ini && $fin ? $ini . ' a ' . $fin : ($ini ?? 'hasta las ' . $fin);
    }

    // Logo propio de la cotización (si no tiene, se usa el del negocio)
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    // Vencida: pasó la fecha de validez y sigue pendiente
    public function getVencidaAttribute(): bool
    {
        return $this->estado === 'pendiente' && $this->vence && $this->vence->lt(today());
    }

    public function getEstadoTextoAttribute(): string
    {
        return $this->vencida ? 'Vencida' : (self::ESTADOS[$this->estado][0] ?? 'Pendiente');
    }

    public static function siguienteNumero(): string
    {
        $max = (int) static::query()->where('numero', 'like', 'COT-%')->selectRaw('MAX(CAST(SUBSTRING(numero, 5) AS UNSIGNED)) m')->value('m');
        return 'COT-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    // Enlace que se le manda al cliente: lo abre sin entrar al sistema (firmado, no se puede adivinar)
    public function getUrlPublicaAttribute(): string
    {
        return url(URL::signedRoute('cotizaciones.publica', ['cotizacion' => $this->id], null, false));
    }

    public function mensajeWhatsapp(string $negocio): string
    {
        return "Hola" . ($this->cliente_nombre ? ' ' . strtok($this->cliente_nombre, ' ') : '') . " 👋, le comparto la cotización {$this->numero} de {$negocio}"
            . ($this->asunto ? " ({$this->asunto})" : '') . " por " . dinero($this->total) . ".\n\nPuede verla y descargarla aquí: " . $this->url_publica
            . ($this->vence ? "\n\nVálida hasta el " . $this->vence->format('d/m/Y') . '.' : '');
    }
}
