<?php

namespace App\Console\Commands;

use App\Models\Asesor;
use App\Models\Tienda;
use App\Models\User;
use App\Support\TiendaActual;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Auditoría de LocalPro: configuración, datos y todas las pantallas.
 * Uso: php artisan localpro:auditoria
 * Las pantallas se abren dentro de una transacción que se deshace al final: no deja cambios en la base.
 */
class Auditoria extends Command
{
    protected $signature = 'localpro:auditoria {--sin-pantallas : Solo revisa configuración y datos}';
    protected $description = 'Revisa configuración, datos y abre todas las pantallas buscando errores';

    private array $informe = [];
    private int $errores = 0;
    private int $avisos = 0;

    public function handle(): int
    {
        $this->line('');
        $this->info('══════ AUDITORÍA LOCALPRO · ' . now()->format('d/m/Y H:i') . ' ══════');

        $this->seccion('1. Configuración del servidor');
        $this->configuracion();

        $this->seccion('2. Tiendas y planes');
        $this->tiendas();

        $this->seccion('3. Pagos y comisiones');
        $this->pagos();

        $this->seccion('4. Red de asesores');
        $this->asesores();

        $this->seccion('5. Ventas, cajas e inventario');
        $this->ventas();

        if (!$this->option('sin-pantallas')) {
            $this->seccion('6. Pantallas (se abren como cada tipo de usuario)');
            $this->pantallas();
        }

        $this->line('');
        $resumen = "RESULTADO: {$this->errores} error(es) · {$this->avisos} aviso(s)";
        $this->errores ? $this->error($resumen) : $this->info($resumen);
        $this->informe[] = '';
        $this->informe[] = $resumen;
        $archivo = 'auditoria/auditoria-' . now()->format('Ymd-His') . '.txt';
        Storage::disk('local')->put($archivo, implode("\n", $this->informe));
        $this->line('Informe guardado en storage/app/' . $archivo);

        return self::SUCCESS;
    }

    // ---------------- utilidades ----------------
    private function seccion(string $t): void
    {
        $this->line('');
        $this->line("<fg=green;options=bold>■ $t</>");
        $this->informe[] = '';
        $this->informe[] = "■ $t";
    }

    private function ok(string $m): void { $this->line("  <fg=green>✔</> $m"); $this->informe[] = "  OK    $m"; }
    private function aviso(string $m): void { $this->avisos++; $this->line("  <fg=yellow>▲</> $m"); $this->informe[] = "  AVISO $m"; }
    private function falla(string $m): void { $this->errores++; $this->line("  <fg=red>✖</> $m"); $this->informe[] = "  ERROR $m"; }

    private function revisar(string $titulo, callable $fn, bool $esError = false, ?callable $detalle = null): void
    {
        try {
            $lista = collect($fn());
            if ($lista->isEmpty()) { $this->ok($titulo . ': bien'); return; }
            $txt = $titulo . ': ' . $lista->count() . ($detalle ? ' → ' . $lista->take(8)->map($detalle)->implode(' · ') . ($lista->count() > 8 ? ' …' : '') : '');
            $esError ? $this->falla($txt) : $this->aviso($txt);
        } catch (Throwable $e) {
            $this->aviso($titulo . ': no se pudo revisar (' . mb_substr($e->getMessage(), 0, 120) . ')');
        }
    }

    // ---------------- 1. configuración ----------------
    private function configuracion(): void
    {
        config('app.debug') && app()->environment('production')
            ? $this->falla('APP_DEBUG=true en producción: si algo falla, el cliente ve el código y las claves. Ponga APP_DEBUG=false en el .env')
            : (config('app.debug') ? $this->aviso('APP_DEBUG=true (ponga APP_DEBUG=false y APP_ENV=production en el servidor)') : $this->ok('APP_DEBUG apagado'));
        app()->environment('production') ? $this->ok('APP_ENV=production') : $this->aviso('APP_ENV=' . app()->environment() . ' (en el servidor debe ser production)');
        $url = (string) config('app.url');
        str_contains($url, 'localproo.com') ? $this->ok("APP_URL=$url") : $this->aviso("APP_URL=$url (debería ser https://localproo.com: los enlaces de pago, certificados y tiendas usan esta dirección)");
        str_starts_with($url, 'https://') ? null : $this->aviso('APP_URL no empieza por https://');

        $pub = (string) config('services.wompi.public_key');
        $int = (string) config('services.wompi.integrity_secret');
        $prv = (string) config('services.wompi.private_key');
        $evt = (string) config('services.wompi.events_secret');
        if (!$pub || !$int) $this->falla('Wompi: faltan WOMPI_PUBLIC_KEY o WOMPI_INTEGRITY_SECRET');
        else {
            $prod = str_starts_with($pub, 'pub_prod_');
            $prod ? $this->ok('Wompi en producción (cobros reales)') : $this->aviso('Wompi en modo pruebas (sandbox): los pagos no son reales');
            $esperado = $prod ? 'prod_integrity_' : 'test_integrity_';
            str_starts_with($int, $esperado) ? $this->ok('Wompi: secreto de integridad correcto') : $this->falla("Wompi: WOMPI_INTEGRITY_SECRET no empieza por $esperado (¿cruzado con el de eventos?). Los pagos fallarán por firma.");
            $prv ? (str_starts_with($prv, $prod ? 'prv_prod_' : 'prv_test_') ? $this->ok('Wompi: llave privada correcta') : $this->falla('Wompi: la llave privada no es del mismo ambiente que la pública')) : $this->aviso('Wompi: falta WOMPI_PRIVATE_KEY (no se confirman pagos automáticamente)');
            $evt && !str_starts_with($evt, $prod ? 'prod_events_' : 'test_events_') ? $this->aviso('Wompi: WOMPI_EVENTS_SECRET no corresponde al ambiente') : null;
        }

        if (\App\Support\Meta::activo()) {
            $this->ok('Mis redes: app de Meta configurada');
            filled(config('services.meta.verify_token')) ? $this->ok('Mis redes: token del webhook configurado') : $this->falla('Mis redes: falta META_VERIFY_TOKEN (no llegarán los mensajes en tiempo real)');
            try {
                $err = \App\Models\RedCuenta::withoutGlobalScopes()->where('estado', 'error')->count();
                $err ? $this->aviso("Mis redes: $err cuenta(s) con error de conexión (deben volver a conectar)") : null;
                $atr = \App\Models\RedPublicacion::withoutGlobalScopes()->where('estado', 'programada')->where('programada_para', '<', now()->subMinutes(10))->count();
                $atr ? $this->falla("Mis redes: $atr publicación(es) programada(s) atrasada(s): ¿corre el cron?") : null;
            } catch (\Throwable $e) {}
        } else {
            $this->aviso('Mis redes: falta META_APP_ID / META_APP_SECRET (los negocios ven "Muy pronto")');
        }
        is_link(public_path('storage')) || is_dir(public_path('storage')) ? $this->ok('Enlace de imágenes (storage:link) creado') : $this->falla('Falta "php artisan storage:link": los logos y fotos de productos no se ven');
        is_writable(storage_path('logs')) ? $this->ok('Carpeta de logs con permiso de escritura') : $this->falla('storage/logs sin permiso de escritura');

        $log = storage_path('logs/laravel.log');
        if (is_file($log)) {
            $hoy = now()->format('Y-m-d');
            $n = 0; $ult = '';
            $fh = fopen($log, 'r');
            if ($fh) {
                fseek($fh, max(0, filesize($log) - 3_000_000));
                while (($l = fgets($fh)) !== false) {
                    if (str_starts_with($l, '[' . $hoy) && str_contains($l, '.ERROR:')) { $n++; $ult = trim(mb_substr($l, 0, 220)); }
                }
                fclose($fh);
            }
            $n ? $this->aviso("Errores registrados hoy en el log: $n. Último: $ult") : $this->ok('Sin errores registrados hoy en el log');
        }

        try {
            $pend = DB::table('migrations')->count();
            $archivos = count(glob(database_path('migrations/*.php')));
            $pend >= $archivos ? $this->ok('Migraciones al día') : $this->falla('Hay migraciones sin correr: ejecute php artisan migrate --force');
        } catch (Throwable $e) { $this->aviso('No se pudo revisar las migraciones'); }
    }

    // ---------------- 2. tiendas ----------------
    private function tiendas(): void
    {
        $this->ok('Tiendas inscritas: ' . Tienda::count() . ' · activas: ' . Tienda::where('estado', 'activa')->count());
        $this->revisar('Tiendas sin dueño (nadie puede entrar a administrarlas)', fn () => Tienda::whereNotIn('id', User::role('dueno')->whereNotNull('tienda_id')->pluck('tienda_id'))
            ->whereNotIn('id', User::where('es_superadmin', true)->whereNotNull('tienda_id')->pluck('tienda_id'))->get(), true, fn ($t) => "$t->codigo $t->nombre");
        $this->revisar('Tiendas sin plan asignado', fn () => Tienda::whereNull('plan_id')->get(), false, fn ($t) => "$t->codigo $t->nombre");
        $this->revisar('Tiendas con plan vencido (bloqueadas, solo ven "Mi plan")', fn () => Tienda::whereNotNull('plan_vence')->whereDate('plan_vence', '<', today())->get(), false, fn ($t) => "$t->codigo $t->nombre (" . $t->plan_vence->format('d/m/Y') . ')');
        $this->revisar('Tiendas que vencen en los próximos 7 días', fn () => Tienda::whereBetween('plan_vence', [today(), today()->addDays(7)])->get(), false, fn ($t) => "$t->codigo $t->nombre (" . $t->plan_vence->format('d/m/Y') . ')');
        $this->revisar('Tiendas sin código LCT', fn () => Schema::hasColumn('tiendas', 'codigo') ? Tienda::whereNull('codigo')->orWhere('codigo', '')->get() : [], true, fn ($t) => "#$t->id $t->nombre");
        $this->revisar('Códigos de tienda repetidos', fn () => DB::table('tiendas')->select('codigo')->whereNotNull('codigo')->groupBy('codigo')->havingRaw('COUNT(*) > 1')->pluck('codigo'), true, fn ($c) => $c);
        $this->revisar('Tiendas con asesor que no existe o está inactivo', fn () => Tienda::whereNotNull('asesor_id')->whereNotIn('asesor_id', Asesor::where('activo', true)->pluck('id'))->get(), false, fn ($t) => "$t->codigo $t->nombre");
        $this->revisar('Tiendas sin asesor ni venta directa', fn () => Tienda::whereNull('asesor_id')->when(Schema::hasColumn('tiendas', 'venta_directa_id'), fn ($q) => $q->whereNull('venta_directa_id'))->get(), false, fn ($t) => "$t->codigo $t->nombre");
        $this->revisar('Tiendas que no terminaron la configuración inicial', fn () => Tienda::where('onboarding_completo', false)->get(), false, fn ($t) => "$t->codigo $t->nombre");
        $this->revisar('Usuarios sin tienda (no son admin ni asesores)', fn () => User::whereNull('tienda_id')->where(fn ($q) => $q->where('es_superadmin', false)->orWhereNull('es_superadmin'))->whereNotIn('id', Asesor::pluck('user_id'))->get(), false, fn ($u) => $u->email);
        $this->revisar('Correos de usuario repetidos', fn () => DB::table('users')->select('email')->groupBy('email')->havingRaw('COUNT(*) > 1')->pluck('email'), true, fn ($e) => $e);
    }

    // ---------------- 3. pagos ----------------
    private function pagos(): void
    {
        $P = \App\Models\PagoSuscripcion::class;
        $prueba = \App\Models\PagoSuscripcion::PREFIJO_PRUEBA . '%';
        $this->ok('Pagos aprobados: ' . $P::where('estado', 'aprobado')->count() . ' · recaudado (sin pruebas): $' . number_format((float) $P::where('estado', 'aprobado')->where('referencia', 'not like', $prueba)->sum(\App\Models\PagoSuscripcion::sqlEnCop()), 0, ',', '.') . ' (en pesos)');
        $this->revisar('Pagos de Wompi pendientes de hace más de 1 hora (¿corre el cron?)', fn () => $P::where('metodo', 'wompi')->where('estado', 'pendiente')->where('created_at', '<', now()->subHour())->where('created_at', '>', now()->subDays(3))->get(), false, fn ($p) => $p->referencia);
        $this->revisar('Pagos aprobados cuyo valor no es el precio del plan (sin contar pruebas)', fn () => $P::with('plan')->where('estado', 'aprobado')->where('metodo', 'wompi')->where('referencia', 'not like', $prueba)->get()->filter(fn ($p) => $p->plan && round((float) $p->monto, 2) !== round($p->plan->precioEn($p->moneda ?: 'COP'), 2)), false, fn ($p) => "$p->referencia " . $p->monto_texto);
        $this->revisar('Pagos aprobados sin período (no extendieron el plan)', fn () => $P::where('estado', 'aprobado')->whereNull('periodo_desde')->get(), true, fn ($p) => $p->referencia);
        $this->revisar('Tiendas con pago anual aprobado y asesor activo pero SIN comisión generada', function () use ($P, $prueba) {
            return $P::with(['tienda.asesor', 'plan'])->where('estado', 'aprobado')->where('monto', '>', 0)->where('referencia', 'not like', $prueba)->get()
                ->filter(fn ($p) => \App\Support\Comisiones::esAnual($p) && $p->tienda?->asesor?->activo
                    && (float) $p->monto >= array_sum(\App\Support\Comisiones::valores($p->moneda ?: 'COP')['nueva'])
                    && !\App\Models\Comision::where('pago_suscripcion_id', $p->id)->exists());
        }, true, fn ($p) => ($p->tienda->nombre ?? '') . ' ' . $p->referencia);
        $this->revisar('Comisiones de pagos de prueba (no deberían existir)', fn () => \App\Models\Comision::whereIn('pago_suscripcion_id', $P::where('referencia', 'like', $prueba)->pluck('id'))->get(), true, fn ($c) => "#$c->id");
        $this->revisar('Comisiones pendientes de personas inactivas', fn () => \App\Models\Comision::with('asesor')->where('estado', 'pendiente')->whereIn('asesor_id', Asesor::where('activo', false)->pluck('id'))->get(), false, fn ($c) => ($c->asesor->nombre ?? '?') . ' ' . $c->monto_texto);
        $pend = (float) \App\Models\Comision::where('estado', 'pendiente')->where('moneda', 'COP')->sum('monto');
        $otras = \App\Models\Comision::where('estado', 'pendiente')->where('moneda', '<>', 'COP')->selectRaw('moneda, SUM(monto) total')->groupBy('moneda')->pluck('total', 'moneda');
        $this->ok('Comisiones por pagar a la red: $' . number_format($pend, 0, ',', '.') . $otras->map(fn ($t, $m) => ' + ' . \App\Support\Moneda::formato($t, $m, true))->implode(''));
    }

    // ---------------- 4. asesores ----------------
    private function asesores(): void
    {
        $this->ok('Red: ' . Asesor::where('estado', 'activo')->count() . ' activos · ' . Asesor::where('estado', 'capacitacion')->count() . ' haciendo el curso');
        $this->revisar('Asesores sin código LCP', fn () => Asesor::whereNull('codigo')->orWhere('codigo', '')->get(), true, fn ($a) => $a->nombre);
        $this->revisar('Códigos de asesor repetidos', fn () => DB::table('asesores')->select('codigo')->whereNotNull('codigo')->groupBy('codigo')->havingRaw('COUNT(*) > 1')->pluck('codigo'), true, fn ($c) => $c);
        $this->revisar('Asesores sin usuario (no pueden entrar)', fn () => Asesor::whereNotIn('user_id', User::pluck('id'))->get(), true, fn ($a) => $a->codigo);
        $this->revisar('Asesores activos sin el curso aprobado (no entran a su panel)', fn () => Asesor::where('estado', 'activo')->whereNull('curso_aprobado_at')->get(), false, fn ($a) => "$a->codigo $a->nombre");
        $this->revisar('Asesores activos sin cuenta para pagarles', fn () => Asesor::where('activo', true)->where(fn ($q) => $q->whereNull('banco')->orWhere('banco', ''))->get(), false, fn ($a) => "$a->codigo $a->nombre");
        $this->revisar('Asesores activos sin venta nueva en los últimos ' . \App\Support\Comisiones::DIAS_ACTIVO . ' días (su parte queda para LocalPro)',
            fn () => Asesor::where('activo', true)->get()->reject(fn ($a) => \App\Support\Comisiones::vendioEsteMes($a)), false, fn ($a) => "$a->codigo $a->nombre");
        $this->revisar('Líderes de zona con más de ' . Asesor::MAX_ASESORES_ZONA . ' asesores', fn () => Asesor::where('cargo', 'lider_zona')->get()->filter(fn ($l) => Asesor::where('padre_id', $l->id)->where('activo', true)->count() > Asesor::MAX_ASESORES_ZONA), true, fn ($a) => "$a->codigo $a->nombre");
        $this->revisar('Asesores cuyo líder no existe', fn () => Asesor::whereNotNull('padre_id')->whereNotIn('padre_id', Asesor::pluck('id'))->get(), true, fn ($a) => "$a->codigo $a->nombre");
        $this->revisar('Aspirantes estancados en el curso más de 7 días', fn () => Asesor::where('estado', 'capacitacion')->where('created_at', '<', now()->subDays(7))->get(), false, fn ($a) => "$a->codigo $a->nombre");
    }

    // ---------------- 5. ventas ----------------
    private function ventas(): void
    {
        $this->ok('Ventas POS: ' . DB::table('pos_ventas')->where('estado', 'pagada')->count() . ' · hoy: ' . DB::table('pos_ventas')->where('estado', 'pagada')->whereDate('created_at', today())->count());
        $this->revisar('Productos con existencias negativas', fn () => DB::table('producto_variantes as v')->join('productos as p', 'p.id', '=', 'v.producto_id')->where('v.stock', '<', 0)->select('p.nombre', 'v.talla', 'v.color', 'v.stock', 'v.tienda_id')->get(), false, fn ($v) => "$v->nombre " . trim("$v->talla $v->color") . " ($v->stock) T$v->tienda_id");
        $this->revisar('Cajas abiertas hace más de 24 horas (alguien no cerró)', fn () => DB::table('cajas')->where('estado', 'abierta')->where('abierta_en', '<', now()->subDay())->get(), false, fn ($c) => "caja #$c->id T$c->tienda_id");
        $this->revisar('Ventas sin productos', fn () => DB::table('pos_ventas as v')->where('v.estado', 'pagada')->whereNotExists(fn ($q) => $q->from('pos_venta_items as i')->whereColumn('i.pos_venta_id', 'v.id'))->get(), true, fn ($v) => $v->numero ?? "#$v->id");
        $this->revisar('Ventas cuyo total no cuadra con sus productos', fn () => DB::table('pos_ventas as v')->where('v.estado', 'pagada')
            ->selectRaw('v.id, v.numero, v.total, v.descuento, (SELECT COALESCE(SUM(i.subtotal),0) FROM pos_venta_items i WHERE i.pos_venta_id = v.id) items')
            ->get()->filter(fn ($v) => abs(((float) $v->items - (float) ($v->descuento ?? 0)) - (float) $v->total) > 1), true, fn ($v) => ($v->numero ?? "#$v->id") . " total $v->total / productos $v->items");
        $this->revisar('Ventas cuyos pagos no suman el total', function () {
            if (!Schema::hasTable('pos_venta_pagos')) return [];
            return DB::table('pos_ventas as v')->where('v.estado', 'pagada')
                ->selectRaw('v.id, v.numero, v.total, v.cambio, (SELECT COALESCE(SUM(p.monto),0) FROM pos_venta_pagos p WHERE p.pos_venta_id = v.id) pagado')
                ->get()->filter(fn ($v) => (float) $v->pagado + 1 < (float) $v->total);
        }, true, fn ($v) => ($v->numero ?? "#$v->id") . " total $v->total / pagado $v->pagado");
        $this->revisar('Clientes con cédula repetida en la misma tienda', fn () => DB::table('personas')->select('tienda_id', 'numero_documento')->groupBy('tienda_id', 'numero_documento')->havingRaw('COUNT(*) > 1')->get(), false, fn ($p) => "T$p->tienda_id $p->numero_documento");
    }

    // ---------------- 6. pantallas ----------------
    private function pantallas(): void
    {
        $perfiles = [];
        if ($u = User::where('es_superadmin', true)->first()) $perfiles['Administrador LocalPro'] = $u;
        $tienda = Tienda::where('estado', 'activa')->where('onboarding_completo', true)->whereHas('plan')->orderByDesc('id')
            ->get()->first(fn ($t) => User::role('dueno')->where('tienda_id', $t->id)->exists());
        if ($tienda) {
            $perfiles['Dueño de ' . $tienda->nombre] = User::role('dueno')->where('tienda_id', $tienda->id)->first();
            if ($v = User::where('tienda_id', $tienda->id)->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['dueno', 'administrador']))->first()) {
                $perfiles['Vendedor de ' . $tienda->nombre] = $v;
            }
        }
        if ($a = Asesor::with('user')->where('estado', 'activo')->whereNotNull('curso_aprobado_at')->first()) $perfiles['Asesor ' . $a->codigo] = $a->user;
        if ($c = Tienda::where('tipo_negocio', 'conjunto')->first()) {
            if ($d = User::role('dueno')->where('tienda_id', $c->id)->first()) $perfiles['Conjunto ' . $c->nombre] = $d;
        }
        if (!$perfiles) { $this->aviso('No hay usuarios para probar las pantallas'); return; }
        $this->ok('Perfiles de prueba: ' . implode(' · ', array_keys($perfiles)));

        // Valores de ejemplo para las rutas con {parámetro}
        $tid = $tienda?->id;
        $muestra = function (string $p) use ($tid) {
            $q = fn ($tabla, $conTienda = true) => DB::table($tabla)->when($conTienda && $tid && Schema::hasColumn($tabla, 'tienda_id'), fn ($w) => $w->where('tienda_id', $tid))->orderByDesc('id')->value('id');
            return match ($p) {
                'tienda' => $tid ?: DB::table('tiendas')->value('id'),
                'producto' => $q('productos'),
                'cliente' => $q('clientes'),
                'proveedore' => $q('proveedores'),
                'categoria' => $q('categorias'),
                'user' => $q('users'),
                'venta' => $q('pos_ventas'),
                'caja' => $q('cajas'),
                'entrada' => $q('entradas'),
                'asesor' => DB::table('asesores')->value('id'),
                'comision' => DB::table('comisiones')->value('id'),
                'plan' => DB::table('planes')->value('id'),
                'importacion' => $q('importaciones'),
                'unidad' => Schema::hasTable('unidades') ? DB::table('unidades')->value('id') : null,
                'pago' => Schema::hasTable('conjunto_pagos') ? DB::table('conjunto_pagos')->value('id') : null,
                'devolucion' => Schema::hasTable('devoluciones') ? $q('devoluciones') : null,
                'tipo' => 'ventas',
                'n' => 1,
                'paso' => 1,
                'codigo' => DB::table('asesores')->value('codigo'),
                'slug' => DB::table('tiendas')->whereNotNull('slug')->value('slug'),
                'certificado' => DB::table('asesores')->whereNotNull('certificado')->value('certificado'),
                default => null,
            };
        };

        $saltar = ['logout', 'pagar.show', 'pagar.retorno', 'plan.retorno', 'ignition', 'sanctum', 'storage', 'livewire', '_debugbar'];
        $rutas = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => in_array('GET', $r->methods()))
            ->reject(fn ($r) => collect($saltar)->contains(fn ($s) => str_contains((string) $r->getName(), $s) || str_contains($r->uri(), $s)))
            ->reject(fn ($r) => str_contains($r->uri(), 'webhook'));

        DB::beginTransaction();
        $fallas = 0; $probadas = 0;
        try {
            foreach ($perfiles as $nombre => $user) {
                foreach ($rutas as $r) {
                    $uri = $r->uri();
                    $faltan = false;
                    $uri = preg_replace_callback('/\{(\w+)\??\}/', function ($m) use ($muestra, &$faltan) {
                        $v = $muestra($m[1]);
                        if ($v === null) $faltan = true;
                        return (string) $v;
                    }, $uri);
                    if ($faltan) continue;
                    $url = '/' . ltrim($uri, '/');
                    if ($r->getName() && str_contains((string) $r->getName(), 'pagar')) continue;

                    $probadas++;
                    [$estado, $msg] = $this->abrir($url, $user);
                    if ($estado >= 500) {
                        $fallas++;
                        $this->falla("[$nombre] $url → $estado " . $msg);
                    }
                }
            }
        } finally {
            DB::rollBack();
            Auth::guard('web')->logout();
            TiendaActual::set(null);
        }
        $fallas ? null : $this->ok("Se abrieron $probadas pantallas sin errores");
        if ($fallas) $this->line("  ($probadas pantallas probadas)");
    }

    private function abrir(string $url, User $user): array
    {
        try {
            app('auth')->forgetGuards();
            Auth::guard('web')->setUser($user->fresh());
            TiendaActual::set(null);
            $req = Request::create($url, 'GET', [], [], [], ['HTTP_HOST' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost', 'HTTPS' => 'on']);
            $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
            $res = $kernel->handle($req);
            $msg = '';
            if ($res->getStatusCode() >= 500 && property_exists($res, 'exception') && $res->exception) {
                $e = $res->exception;
                $msg = class_basename($e) . ': ' . mb_substr($e->getMessage(), 0, 180) . ' (' . str_replace(base_path() . '/', '', $e->getFile()) . ':' . $e->getLine() . ')';
            }
            $kernel->terminate($req, $res);
            return [$res->getStatusCode(), $msg];
        } catch (Throwable $e) {
            return [599, class_basename($e) . ': ' . mb_substr($e->getMessage(), 0, 180) . ' (' . str_replace(base_path() . '/', '', $e->getFile()) . ':' . $e->getLine() . ')'];
        }
    }
}
