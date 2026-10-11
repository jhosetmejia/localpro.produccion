<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Ajustes generales de la plataforma (WhatsApp de soporte, etc.)
class Ajuste extends Model
{
    protected $table = 'ajustes';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['clave', 'valor'];

    public static function valor(string $clave, $porDefecto = null)
    {
        return static::find($clave)?->valor ?? $porDefecto;
    }

    public static function guardar(string $clave, $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
}
