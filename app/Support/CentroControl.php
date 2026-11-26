<?php

namespace App\Support;

use App\Models\Asesor;
use App\Models\ErrorCliente;
use App\Models\Opinion;
use App\Models\PagoSuscripcion;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-25 (Jhoset): Centro de control (/admin/centro). LocalPro + PFN + NES en vivo en una sola pantalla:
 * números del día, ingresos, pendientes, errores del servidor, fallas del navegador, envíos masivos y actividad.
 * PFN y NES se piden a su /api/lp/panel firmado con la misma clave de LP Chat (config lpchat.origenes.*.secreto).
 */
class CentroControl
{
    private const TZ = 'America/Bogota';

    public const SISTEMAS = [
        'pfn' => ['nombre' => 'Pasto Fight Night', 'url' => 'https://pastofightnight.com', 'color' => '#e5243b'],
        'nes' => ['nombre' => 'Nariño Exposhow', 'url' => 'https://narinoexposhow.com', 'color' => '#f5b400'],
    ];

    /** Todo junto. Cada sistema se guarda 20 s para no golpear los servidores si hay varias pestañas abiertas. */
    public static function todo(bool $fresco = false): array
    {
        $out = ['hora' => now(self::TZ)->format('h:i:s a'), 'sistemas' => []];
        $out['sistemas']['localpro'] = self::guardado('localpro', $fresco, fn () => self::local());
        foreach (array_keys(self::SISTEMAS) as $k) {
            $out['sistemas'][$k] = self::guardado($k, $fresco, fn () => self::remoto($k));
        }
        // Errores del servidor: se agrupan por firma y se ocultan los ya resueltos (deploy/errores-resueltos.txt)
        $resueltos = self::erroresResueltos();
        foreach ($out['sistemas'] as $k => &$s0) {
            if (! empty($s0['errores']['grupos'])) {
                $s0['errores'] = self::limpiarErrores($k, $s0['errores'], $resueltos);
            }
        }
        unset($s0);
        // Fallas del navegador: llegan todas a LocalPro (lp-opinion.js); se reparten por sistema
        foreach ($out['sistemas'] as $k => &$s) {
            try {
                $s['fallas'] = self::fallasNavegador($k);
            } catch (\Throwable $e) {
                $s['fallas'] = null;
            }
        }

        return $out;
    }

    private static function guardado(string $k, bool $fresco, \Closure $fn): array
    {
        if ($fresco) {
            Cache::forget('centro:'.$k);
        }

        return Cache::remember('centro:'.$k, 20, function () use ($fn, $k) {
            $t = microtime(true);
            try {
                $r = $fn();
            } catch (\Throwable $e) {
                $r = ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200)];
            }
            $r['ms'] = (int) round((microtime(true) - $t) * 1000);
            $r['clave'] = $k;

            return $r;
        });
    }

    /** PFN o NES: su resumen firmado. */
    public static function remoto(string $k): array
    {
        $cfg = self::SISTEMAS[$k];
        $secreto = (string) config('lpchat.origenes.'.$k.'.secreto');
        if ($secreto === '') {
            return ['ok' => false, 'nombre' => $cfg['nombre'], 'url' => $cfg['url'], 'error' => 'Falta la clave LPCHAT_SECRETO_'.strtoupper($k).' en el .env de LocalPro.'];
        }
        $ts = (string) time();
        try {
            $r = Http::timeout(12)->connectTimeout(5)->acceptJson()
                ->get($cfg['url'].'/api/lp/panel', ['ts' => $ts, 'f' => hash_hmac('sha256', 'panel:'.$ts, $secreto)]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'nombre' => $cfg['nombre'], 'url' => $cfg['url'], 'error' => 'No responde: '.mb_substr($e->getMessage(), 0, 160)];
        }
        if ($r->status() === 404) {
            return ['ok' => false, 'nombre' => $cfg['nombre'], 'url' => $cfg['url'], 'error' => 'Todavía no tiene la conexión del Centro de control (se está desplegando).'];
        }
        if ($r->status() === 403) {
            return ['ok' => false, 'nombre' => $cfg['nombre'], 'url' => $cfg['url'], 'error' => 'La clave no coincide (LPCHAT_SECRETO de '.strtoupper($k).' ≠ LPCHAT_SECRETO_'.strtoupper($k).' de LocalPro) o la hora de los servidores está corrida.'];
        }
        if (! $r->successful() || ! is_array($r->json())) {
            return ['ok' => false, 'nombre' => $cfg['nombre'], 'url' => $cfg['url'], 'error' => 'Respondió con error '.$r->status().'.'];
        }

        return $r->json() + ['nombre' => $cfg['nombre'], 'url' => $cfg['url']];
    }

    /** LocalPro (este mismo sistema), con la misma forma que PFN y NES. */
    public static function local(): array
    {
        $out = ['ok' => true, 'sistema' => 'LocalPro', 'nombre' => 'LocalPro', 'url' => url('/'), 'version' => self::version()];
        foreach (['kpis', 'ingresos', 'pendientes', 'errores', 'envios', 'actividad', 'resumen'] as $p) {
            try {
                $m = 'l'.ucfirst($p);
                $out[$p] = self::$m();
            } catch (\Throwable $e) {
                $out[$p] = null;
                $out['fallas_panel'][] = $p.': '.mb_substr($e->getMessage(), 0, 200);
            }
        }

        return $out;
    }

    private static function hoy(): Carbon
    {
        return now(self::TZ)->startOfDay()->setTimezone(config('app.timezone'));
    }

    private static function lKpis(): array
    {
        $hoy = self::hoy();
        $k = [];
        $k[] = ['label' => 'Empresas', 'valor' => Tienda::count(), 'hoy' => Tienda::where('created_at', '>=', $hoy)->count(), 'link' => route('admin.tiendas.index'),
            'sub' => Tienda::where('estado', 'activa')->count().' activas · '.Tienda::whereNotNull('plan_vence')->where('plan_vence', '>=', today())->count().' pagando'];
        $k[] = ['label' => 'Usuarios', 'valor' => User::count(), 'hoy' => User::where('created_at', '>=', $hoy)->count(), 'link' => route('admin.usuarios')];
        if (Schema::hasTable('lpchat_usuarios')) {
            $k[] = ['label' => 'LP Chat (personas)', 'valor' => DB::table('lpchat_usuarios')->count(), 'hoy' => DB::table('lpchat_usuarios')->where('created_at', '>=', $hoy)->count()];
        }
        if (Schema::hasTable('lpchat_mensajes')) {
            $k[] = ['label' => 'Mensajes LP Chat hoy', 'valor' => DB::table('lpchat_mensajes')->where('created_at', '>=', $hoy)->count()];
        }
        if (Schema::hasTable('cut_visitas')) {
            $k[] = ['label' => 'LocalProCut · visitas hoy', 'valor' => DB::table('cut_visitas')->where('created_at', '>=', $hoy)->count(), 'link' => route('admin.cut')];
        }
        $k[] = ['label' => 'Ventas de las tiendas hoy', 'valor' => DB::table('pos_ventas')->where('estado', 'pagada')->where('created_at', '>=', $hoy)->count()];
        $k[] = ['label' => 'Personas en la red', 'valor' => Asesor::where('activo', true)->count(), 'link' => route('admin.red.index')];
        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            $k[] = ['label' => 'Conectados ahora', 'valor' => DB::table('sessions')->where('last_activity', '>=', time() - 300)->count(), 'sub' => 'últimos 5 min'];
        }

        return $k;
    }

    private static function lIngresos(): array
    {
        $ap = fn () => PagoSuscripcion::where('estado', 'aprobado');
        $s = fn ($desde) => (int) $ap()->where('created_at', '>=', $desde)->sum(PagoSuscripcion::sqlEnCop());

        return [
            'moneda' => 'COP',
            'hoy' => $s(self::hoy()), 'semana' => $s(now()->subDays(7)), 'mes' => $s(now(self::TZ)->startOfMonth()->setTimezone(config('app.timezone'))),
            'total' => (int) $ap()->sum(PagoSuscripcion::sqlEnCop()), 'etiqueta_total' => 'Total histórico (planes)',
            'link' => route('admin.inicio'),
            'ultimos' => PagoSuscripcion::with('tienda:id,nombre')->where('estado', 'aprobado')->latest()->take(10)->get()
                ->map(fn ($p) => ['fecha' => $p->created_at->timezone(self::TZ)->format('Y-m-d H:i'), 'concepto' => $p->donacion ? 'Donación' : 'Plan', 'persona' => $p->tienda->nombre ?? '—',
                    'monto' => (int) round((float) PagoSuscripcion::where('id', $p->id)->sum(PagoSuscripcion::sqlEnCop()))])->all(),
        ];
    }

    /** Los 4 cuadros de cada sistema: registrados, ingresos del año, (fallas: se arman con errores + fallas) y preguntas de Mía. */
    private static function lResumen(): array
    {
        $anio = now(self::TZ)->year;
        $desde = now(self::TZ)->startOfYear()->setTimezone(config('app.timezone'));

        return [
            'registrados' => ['n' => User::count(), 'hoy' => User::where('created_at', '>=', self::hoy())->count(), 'label' => 'Usuarios registrados',
                'sub' => Tienda::count().' empresas', 'link' => route('admin.usuarios')],
            'ingresos_anio' => ['n' => (int) PagoSuscripcion::where('estado', 'aprobado')->where('created_at', '>=', $desde)->sum(PagoSuscripcion::sqlEnCop()),
                'anio' => $anio, 'link' => route('admin.pagos')],
            'mia' => ['n' => Schema::hasTable('asistente_mensajes') ? DB::table('asistente_mensajes')->where('sin_respuesta', true)->where('resuelta', false)->count() : null,
                'link' => url('/admin/asistente')],
        ];
    }

    private static function lPendientes(): array
    {
        $p = [];
        $n = PagoSuscripcion::where('estado', 'pendiente')->count();
        $p[] = ['texto' => 'Pagos de planes pendientes', 'n' => $n, 'nivel' => $n ? 'amarillo' : 'ok', 'link' => route('admin.inicio')];
        $n = Tienda::whereNotNull('plan_vence')->where('plan_vence', '<', today())->count();
        $p[] = ['texto' => 'Empresas con el plan vencido', 'n' => $n, 'nivel' => 'info', 'link' => route('admin.tiendas.index')];
        $n = Opinion::where('estado', 'nuevo')->count();
        $p[] = ['texto' => 'Opiniones nuevas sin revisar', 'n' => $n, 'nivel' => $n ? 'amarillo' : 'ok', 'link' => route('admin.opiniones')];
        $n = (int) DB::table('comisiones')->where('estado', 'pendiente')->count();
        $p[] = ['texto' => 'Comisiones de asesores por pagar', 'n' => $n, 'nivel' => 'info'];
        if (Schema::hasTable('asistente_mensajes')) {
            $n = DB::table('asistente_mensajes')->where('sin_respuesta', true)->where('resuelta', false)->count();
            $p[] = ['texto' => 'Preguntas que Mía no supo responder', 'n' => $n, 'nivel' => $n ? 'amarillo' : 'ok', 'link' => url('/admin/asistente')];
        }

        return $p;
    }

    private static function lErrores(): array
    {
        $carpeta = storage_path('logs');
        $archivos = array_filter([$carpeta.'/laravel.log', $carpeta.'/laravel-'.now()->format('Y-m-d').'.log', $carpeta.'/laravel-'.now()->subDay()->format('Y-m-d').'.log'], 'is_file');
        $desde = now()->subDay();
        $grupos = [];
        $total = 0;
        foreach ($archivos as $a) {
            $tam = @filesize($a) ?: 0;
            $fh = @fopen($a, 'r');
            if (! $fh) {
                continue;
            }
            if ($tam > 3_000_000) {
                fseek($fh, -3_000_000, SEEK_END);
            }
            while (($l = fgets($fh)) !== false) {
                if (! preg_match('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)$/', $l, $m)) {
                    continue;
                }
                try {
                    $f = Carbon::parse($m[1]);
                } catch (\Throwable $e) {
                    continue;
                }
                if ($f->lt($desde)) {
                    continue;
                }
                $total++;
                $msg = mb_substr(trim(preg_replace('/\s*\{"(exception|userId)".*$/s', '', $m[3])), 0, 300);
                $archivo = preg_match('/ at (\/[^\s]+:\d+)/', $m[3], $x) ? preg_replace('#^.*?/(app|resources|routes|config|database)/#', '$1/', $x[1]) : '';
                $clave = md5(preg_replace('/\d+/', '#', $msg));
                $grupos[$clave] ??= ['mensaje' => $msg, 'veces' => 0, 'ultima' => '', 'archivo' => $archivo];
                $grupos[$clave]['veces']++;
                $grupos[$clave]['ultima'] = $f->timezone(self::TZ)->format('Y-m-d H:i');
            }
            fclose($fh);
        }
        usort($grupos, fn ($a, $b) => strcmp($b['ultima'], $a['ultima']));

        return ['n24' => $total, 'grupos' => array_slice($grupos, 0, 12)];
    }

    private static function lEnvios(): array
    {
        $out = ['campanas' => [], 'correo' => []];
        if (Schema::hasTable('prospecto_envios')) {
            $f = DB::table('prospecto_envios')->where('created_at', '>=', now()->subDays(7))
                ->select('estado', DB::raw('count(*) as n'), DB::raw('max(created_at) as ultimo'))->groupBy('estado')->get();
            if ($f->isNotEmpty()) {
                $out['campanas'][] = ['titulo' => 'Correos a prospectos (7 días)', 'enviados' => (int) $f->sum('n'),
                    'detalle' => $f->map(fn ($x) => $x->estado.': '.$x->n)->implode(' · '),
                    'ultimo' => Carbon::parse($f->max('ultimo'))->timezone(self::TZ)->format('Y-m-d H:i')];
            }
        }

        return $out;
    }

    private static function lActividad(): array
    {
        $a = [];
        foreach (Tienda::latest()->take(6)->get(['nombre', 'created_at']) as $t) {
            $a[] = [$t->created_at, '🏪 Empresa nueva · '.$t->nombre];
        }
        foreach (PagoSuscripcion::with('tienda:id,nombre')->where('estado', 'aprobado')->latest()->take(6)->get() as $p) {
            $a[] = [$p->created_at, '💰 Pago de plan · '.($p->tienda->nombre ?? '—')];
        }
        if (Schema::hasTable('lpchat_usuarios')) {
            foreach (DB::table('lpchat_usuarios')->orderByDesc('id')->limit(6)->get(['nombre', 'origen', 'created_at']) as $u) {
                $a[] = [$u->created_at, '💬 Entró a LP Chat · '.$u->nombre.($u->origen ? ' ('.strtoupper($u->origen).')' : '')];
            }
        }
        foreach (Opinion::latest()->take(4)->get(['tipo', 'mensaje', 'created_at']) as $o) {
            $a[] = [$o->created_at, (Opinion::ICONOS[$o->tipo] ?? '💬').' Opinión · '.mb_strimwidth((string) $o->mensaje, 0, 70, '…')];
        }
        $a = array_filter($a, fn ($x) => $x[0]);
        usort($a, fn ($x, $y) => Carbon::parse($y[0])->timestamp <=> Carbon::parse($x[0])->timestamp);

        return array_map(fn ($x) => ['fecha' => Carbon::parse($x[0])->timezone(self::TZ)->format('Y-m-d H:i'), 'texto' => $x[1]], array_slice($a, 0, 12));
    }

    /** Firma corta de un error del servidor: el mensaje sin el contexto JSON del final, sin números (ids, horas, firmas). */
    public static function firmaError(string $msg): string
    {
        $m = preg_replace('/\s*\{".*$/s', '', $msg);
        $m = preg_replace('/^(ERROR-[A-Z0-9]+\s+)?([\w\\\\]+(Exception|Error):\s*)?/', '', (string) $m); // "ERROR-FI897G RuntimeException: …" = "…"
        $m = preg_replace('/[0-9a-f]{16,}/i', '#', (string) $m);

        return substr(md5(preg_replace('/\d+/', '#', trim((string) $m))), 0, 10);
    }

    /**
     * deploy/errores-resueltos.txt — una línea por error ya arreglado:  "S-nes-1a2b3c4d5e AAAA-MM-DD HH:MM comentario"
     * (hora de Colombia). Se oculta si no volvió a pasar después de esa hora; si vuelve a pasar, aparece otra vez.
     */
    private static function erroresResueltos(): array
    {
        $f = base_path('deploy/errores-resueltos.txt');
        $r = [];
        if (! is_file($f)) {
            return $r;
        }
        foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
            if (preg_match('/^\s*S-([a-z]+)-([0-9a-f]{10})\s+(\d{4}-\d{2}-\d{2} \d{1,2}:\d{2})/', $l, $m)) {
                $r[$m[1].'-'.$m[2]] = max($r[$m[1].'-'.$m[2]] ?? '', $m[3]);
            }
        }

        return $r;
    }

    private static function limpiarErrores(string $k, array $err, array $resueltos): array
    {
        $g = [];
        foreach ($err['grupos'] as $x) {
            $id = self::firmaError((string) ($x['mensaje'] ?? ''));
            $ult = (string) ($x['ultima'] ?? '');
            if (isset($resueltos[$k.'-'.$id]) && $ult !== '' && $ult <= $resueltos[$k.'-'.$id]) {
                continue;
            }
            if (isset($g[$id])) {
                $g[$id]['veces'] += (int) ($x['veces'] ?? 1);
                $g[$id]['ultima'] = max($g[$id]['ultima'], $ult);
                continue;
            }
            $x['id'] = 'S-'.$k.'-'.$id;
            $x['veces'] = (int) ($x['veces'] ?? 1);
            $g[$id] = $x;
        }
        $g = array_values($g);
        usort($g, fn ($a, $b) => strcmp($b['ultima'], $a['ultima']));
        $err['grupos'] = $g;
        $err['n24'] = array_sum(array_column($g, 'veces'));

        return $err;
    }

    /** Fallas del navegador sin resolver (llegan todas a LocalPro), por sistema: pfn, nes o el resto (LocalPro). */
    public static function fallasNavegador(string $k): array
    {
        $q = fn () => ErrorCliente::where('resuelto', false)
            ->when(in_array($k, ['pfn', 'nes'], true), fn ($w) => $w->where('modulo', $k), fn ($w) => $w->where(fn ($x) => $x->whereNotIn('modulo', ['pfn', 'nes'])->orWhereNull('modulo')));

        return [
            'abiertas' => $q()->count(),
            'hoy' => $q()->where('ultima_vez', '>=', self::hoy())->count(),
            'lista' => $q()->orderByDesc('ultima_vez')->take(8)->get()->map(fn ($e) => [
                'id' => 'E'.$e->id, 'mensaje' => mb_strimwidth((string) $e->mensaje, 0, 160, '…'), 'pagina' => $e->pagina, 'veces' => $e->veces,
                'ultima' => optional($e->ultima_vez)->timezone(self::TZ)->format('Y-m-d H:i'), 'modulo' => $e->modulo,
            ])->all(),
            'link' => route('admin.fallas'),
        ];
    }

    private static function version(): string
    {
        $head = base_path('.git/HEAD');
        if (! is_file($head)) {
            return '';
        }
        $ref = trim((string) @file_get_contents($head));
        $sha = str_starts_with($ref, 'ref: ') ? trim((string) @file_get_contents(base_path('.git/'.substr($ref, 5)))) : $ref;

        return substr($sha, 0, 7);
    }
}
