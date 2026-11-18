<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

/** 2026-11-17 (Jhoset): cuenta de LP Chat (guard "lpchat"). Se entra con correo o celular. */
class LpchatUsuario extends Authenticatable
{
    protected $table = 'lpchat_usuarios';

    protected $fillable = ['nombre', 'email', 'celular', 'password', 'foto', 'bio', 'ciudad', 'etiqueta', 'conocer', 'codigo', 'user_id', 'tienda_id', 'origen', 'visto_at', 'pro_hasta', 'sin_limite', 'menor'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['conocer' => 'boolean', 'visto_at' => 'datetime', 'pro_hasta' => 'datetime', 'sin_limite' => 'boolean', 'menor' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $u) {
            if (! $u->codigo) {
                do {
                    $c = Str::lower(Str::random(8));
                } while (static::where('codigo', $c)->exists());
                $u->codigo = $c;
            }
        });
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (! $this->foto) {
            return null;
        }

        return preg_match('#^https?://#', $this->foto) ? $this->foto : asset('storage/'.$this->foto);
    }

    public function getLinkAttribute(): string
    {
        return route('lpchat.link', $this->codigo);
    }

    /** Celular colombiano a solo dígitos con 57 adelante (3001234567 -> 573001234567). */
    public static function normalizarCelular(?string $c): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $c);
        if ($d === '') {
            return null;
        }
        if (strlen($d) === 10 && $d[0] === '3') {
            $d = '57'.$d;
        }

        return strlen($d) >= 7 && strlen($d) <= 15 ? $d : null;
    }

    /** "correo o celular" -> [campo, valor] o null. */
    public static function identificador(?string $x): ?array
    {
        $x = trim((string) $x);
        if (filter_var($x, FILTER_VALIDATE_EMAIL)) {
            return ['email', mb_strtolower($x)];
        }
        $c = self::normalizarCelular($x);

        return $c ? ['celular', $c] : null;
    }

    public static function buscarPorIdentificador(?string $x): ?self
    {
        $id = self::identificador($x);

        return $id ? static::where($id[0], $id[1])->first() : null;
    }
}
