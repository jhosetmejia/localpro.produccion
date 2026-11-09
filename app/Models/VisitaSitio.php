<?php

namespace App\Models;

use App\Support\GeoIp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Una visita a LocalProo (30 min sin actividad = visita nueva). Ver Admin → Visitas y uso. */
class VisitaSitio extends Model
{
    protected $table = 'visitas_sitio';
    protected $guarded = ['id'];
    protected $casts = ['nuevo' => 'boolean', 'geo_consultada' => 'boolean', 'datos_moviles' => 'boolean', 'vpn' => 'boolean', 'es_app' => 'boolean', 'ultima_vez' => 'datetime'];

    public function paginasVistas() { return $this->hasMany(VisitaPagina::class, 'visita_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function tienda() { return $this->belongsTo(Tienda::class); }

    /** Nombres claros de las secciones (por la primera parte del nombre de la ruta). */
    public const SECCIONES = [
        'panel' => 'Inicio del panel', 'pos' => 'Punto de venta (POS)', 'cajas' => 'Caja', 'productos' => 'Productos', 'inventario' => 'Inventario',
        'clientes' => 'Clientes', 'ventas' => 'Ventas', 'compras' => 'Compras', 'reportes' => 'Reportes', 'facturacion' => 'Facturación',
        'negocio' => 'Mi negocio', 'plan' => 'Plan y pagos', 'redes' => 'Mis redes', 'cut' => 'LocalProCut', 'catalogo' => 'Tienda en línea (pública)',
        'marketplace' => 'Marketplace', 'blog' => 'Blog', 'registro' => 'Registro', 'login' => 'Iniciar sesión', 'funciones' => 'Funciones',
        'obituarios' => 'Obituarios', 'eventos' => 'Eventos', 'contabilidad' => 'Contabilidad', 'nomina' => 'Nómina', 'consultorio' => 'Consultorio',
        'odonto' => 'Odontología', 'citas' => 'Citas', 'alquiler' => 'Alquiler', 'separados' => 'Separados', 'cotizacion' => 'Cotizaciones',
        'pedidos' => 'Pedidos', 'carta' => 'Carta virtual', 'enlaces' => 'Mis enlaces', 'giro360' => 'App 360', 'admin' => 'Administración',
        'asesor' => 'Asesores', 'curso' => 'Curso', 'beneficios' => 'Beneficios', 'primeros' => 'Primeros pasos', 'legal' => 'Páginas legales',
        'conjunto' => 'Conjuntos', 'instituto' => 'Institutos', 'pantallas' => 'Pantallas', 'cuenta' => 'Mi cuenta', 'bienvenida' => 'Bienvenida',
    ];

    public static function seccion(?string $ruta, ?string $pagina = null): string
    {
        if ($ruta) {
            $k = strtok($ruta, '.');
            return self::SECCIONES[$k] ?? ucfirst(str_replace(['-', '_'], ' ', $k));
        }
        return $pagina ?: '—';
    }

    public static function ubicarPendientes(int $limite = 200): int
    {
        if (!Cache::add('sitio_geo_intento', 1, 20)) return 0;
        $pend = static::where('geo_consultada', false)->whereNotNull('ip')->orderByDesc('id')->limit($limite)->get(['id', 'ip']);
        if ($pend->isEmpty()) return 0;
        $geo = GeoIp::consultar($pend->pluck('ip')->all());
        $n = 0;
        foreach ($pend as $v) {
            if (!isset($geo[$v->ip])) continue;
            static::whereKey($v->id)->update($geo[$v->ip] + ['geo_consultada' => true, 'ip' => null]);
            $n++;
        }
        static::where('geo_consultada', false)->where('created_at', '<', now()->subDays(2))->update(['ip' => null, 'geo_consultada' => true]);
        return $n;
    }
}
