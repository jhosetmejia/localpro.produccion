<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

/** 2026-11-17 (Jhoset): cuenta de LP Chat (guard "lpchat"). Se entra con correo o celular. */
class LpchatUsuario extends Authenticatable
{
    protected $table = 'lpchat_usuarios';

    protected $fillable = ['nombre', 'email', 'celular', 'password', 'foto', 'bio', 'ciudad', 'etiqueta', 'conocer', 'codigo', 'usuario', 'user_id', 'tienda_id', 'origen', 'visto_at', 'pro_hasta', 'sin_limite', 'menor'];

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
            // 2026-11-25 (Jhoset): usuario único (el @) que vale en LP Chat, PFN y NES.
            if (! $u->usuario && \Illuminate\Support\Facades\Schema::hasColumn('lpchat_usuarios', 'usuario')) {
                $base = $u->nombre;
                if ($u->tienda_id && ($slug = \App\Models\Tienda::whereKey($u->tienda_id)->value('slug'))) {
                    $base = $slug;
                }
                $u->usuario = self::usuarioLibre((string) $base, null, $u->tienda_id ? (int) $u->tienda_id : null);
            }
        });
    }

    /** Palabras que nunca pueden ser el @ de una persona (páginas del sitio). */
    public const USUARIOS_RESERVADOS = ['admin', 'administrador', 'api', 'app', 'chat', 'lpchat', 'login', 'logout', 'entrar', 'salir', 'registro',
        'register', 'panel', 'dashboard', 'soporte', 'ayuda', 'equipo', 'mia', 'localpro', 'localproo', 'pfn', 'nes', 'blog', 'tienda', 'tiendas',
        'eventos', 'evento', 'entradas', 'cut', 'planes', 'pagos', 'terminos', 'privacidad', 'contacto', 'perfil', 'usuario', 'usuarios', 'estado',
        'peleadores', 'modelos', 'empresas', 'marcas', 'null', 'undefined', 'www'];

    /** "Rafael Gómez" -> "rafael-gomez" (solo letras sin tildes, números y guiones, máx 40). */
    public static function usuarioBase(string $texto): string
    {
        $s = trim(mb_substr(Str::slug($texto), 0, 40), '-');

        return $s !== '' ? $s : 'persona';
    }

    /** ¿Ese @ lo puede usar esta cuenta? No lo tiene otra persona, no es un negocio ajeno ni una palabra reservada. */
    public static function usuarioDisponible(string $usuario, ?int $exceptoId = null, ?int $tiendaId = null): bool
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $usuario) || strlen($usuario) < 3 || strlen($usuario) > 60
            || in_array($usuario, self::USUARIOS_RESERVADOS, true) || preg_match('/^pfn-?\d+$/', $usuario)) {
            return false;
        }
        if (static::where('usuario', $usuario)->when($exceptoId, fn ($q) => $q->whereKeyNot($exceptoId))->exists()) {
            return false;
        }
        // El @ de un negocio (localproo.com/@negocio) solo lo usa la cuenta de ese negocio.
        $tienda = \App\Models\Tienda::where('slug', $usuario)->value('id');

        return ! $tienda || ($tiendaId && (int) $tienda === $tiendaId);
    }

    /** Primer @ libre a partir de un nombre: rafael-gomez, si no rafael-gomez-2, -3... */
    public static function usuarioLibre(string $texto, ?int $exceptoId = null, ?int $tiendaId = null): string
    {
        $base = self::usuarioBase($texto);
        if (strlen($base) < 3) {
            $base .= '-lp';
        }
        $c = $base;
        $n = 1;
        while (! self::usuarioDisponible($c, $exceptoId, $tiendaId)) {
            $c = $base.'-'.(++$n);
        }

        return $c;
    }

    /** Link corto de la persona: localproo.com/@rafael-gomez */
    public function getArrobaUrlAttribute(): ?string
    {
        return $this->usuario ? url('/@'.$this->usuario) : null;
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
        // 2026-11-25: link estándar con el usuario único: localproo.com/chat/rafael-gomez
        return $this->usuario ? url('/chat/'.$this->usuario) : route('lpchat.link', $this->codigo);
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
