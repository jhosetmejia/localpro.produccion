<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Prospecto comercial (firma contable, contador…) al que LocalProo le escribe con la secuencia de correos
class Prospecto extends Model
{
    protected $table = 'prospectos';

    protected $fillable = [
        'empresa', 'nombre', 'correo', 'telefono', 'ciudad', 'web', 'fuente', 'segmento',
        'estado', 'paso', 'ultimo_envio', 'proximo_envio', 'intentos_busqueda', 'notas',
    ];

    protected $casts = [
        'ultimo_envio' => 'datetime',
        'proximo_envio' => 'date',
        'baja_at' => 'datetime',
    ];

    public const ESTADOS = [
        'sin_correo' => ['Buscando correo', 'secondary'],
        'nuevo' => ['Listo para escribir', 'info'],
        'en_secuencia' => ['En secuencia', 'primary'],
        'terminado' => ['Secuencia terminada', 'dark'],
        'respondio' => ['Respondió', 'warning'],
        'registrado' => ['Se registró', 'success'],
        'baja' => ['Pidió no escribir', 'danger'],
        'rebotado' => ['Correo no existe', 'danger'],
        'descartado' => ['Descartado', 'secondary'],
    ];

    // Estados en los que nunca se le vuelve a escribir
    public const CERRADOS = ['respondio', 'registrado', 'baja', 'rebotado', 'descartado', 'terminado'];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            $p->token = $p->token ?: Str::random(32);
            $p->correo = $p->correo ? strtolower(trim($p->correo)) : null;
        });
    }

    public function envios()
    {
        return $this->hasMany(ProspectoEnvio::class);
    }

    public function saludo(): string
    {
        $n = trim((string) $this->nombre);
        return $n !== '' ? 'Hola ' . strtok($n, ' ') : 'Buenos días';
    }
}
