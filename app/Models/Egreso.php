<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Egreso extends Model
{
    use PerteneceATienda;

    protected $table = 'egresos';

    protected $fillable = ['user_id', 'fecha', 'concepto', 'categoria', 'valor', 'metodo_pago_id', 'caja_id', 'de_admin', 'soporte_path', 'nota'];

    protected $casts = ['fecha' => 'date', 'valor' => 'float', 'de_admin' => 'boolean'];

    public const CATEGORIAS = [
        'arriendo' => ['Arriendo', 'fa-house'],
        'servicios' => ['Servicios (luz, agua, internet)', 'fa-bolt'],
        'nomina' => ['Pagos a empleados', 'fa-user-tie'],
        'mercancia' => ['Compra de mercancía', 'fa-truck-ramp-box'],
        'transporte' => ['Transporte y domicilios', 'fa-motorcycle'],
        'insumos' => ['Insumos y papelería', 'fa-box-open'],
        'aseo' => ['Aseo y cafetería', 'fa-broom'],
        'mantenimiento' => ['Mantenimiento y arreglos', 'fa-screwdriver-wrench'],
        'publicidad' => ['Publicidad', 'fa-bullhorn'],
        'impuestos' => ['Impuestos y trámites', 'fa-landmark'],
        'otros' => ['Otros', 'fa-receipt'],
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function metodo() { return $this->belongsTo(MetodoPago::class, 'metodo_pago_id'); }
    public function caja() { return $this->belongsTo(Caja::class); }

    /** Lo que puede ver este usuario: todo si tiene "ver-egresos-todos"; si no, solo los de vendedores (los suyos y de sus compañeros) */
    public function scopeVisiblesPara($q, User $u)
    {
        return $u->can('ver-egresos-todos') ? $q : $q->where('de_admin', false);
    }

    public function getCategoriaNombreAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria][0] ?? 'Otros';
    }

    public function getCategoriaIconoAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria][1] ?? 'fa-receipt';
    }

    public function getSoporteUrlAttribute(): ?string
    {
        return $this->soporte_path ? Storage::disk('public')->url($this->soporte_path) : null;
    }

    /** ¿Lo puede borrar este usuario? El administrador cualquiera; el vendedor solo los suyos mientras su caja siga abierta (o sin caja, el mismo día) */
    public function puedeBorrar(User $u): bool
    {
        if ($u->can('ver-egresos-todos')) {
            return true;
        }
        if ((int) $this->user_id !== (int) $u->id) {
            return false;
        }
        return $this->caja_id ? optional($this->caja)->estado === 'abierta' : $this->created_at?->isToday();
    }
}
