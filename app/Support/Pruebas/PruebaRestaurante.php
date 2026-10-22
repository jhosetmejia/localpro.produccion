<?php

namespace App\Support\Pruebas;

use App\Http\Controllers\pedidosController;
use App\Http\Controllers\posController;
use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\NegocioRol;
use App\Models\Pedido;
use App\Models\PedidoEstacion;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\User;
use App\Support\CrearTienda;
use App\Support\RolesNegocio;
use App\Support\TiendaActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Prueba de punta a punta del sistema de restaurante, con las mismas pantallas que usa la gente:
 * crea un restaurante como lo haría un cliente nuevo, revisa sus roles por defecto y hace el recorrido
 * mesero → caja → cocina/bar → aviso al mesero → entregado. Todo corre en una transacción que al final
 * se deshace: no queda nada guardado.
 */
class PruebaRestaurante
{
    private array $pasos = [];
    private ?Tienda $t = null;
    private array $u = [];      // mesero, caja, cocina, bar
    private array $prod = [];   // hamburguesa, limonada (variantes)

    // Ciclo de vida que se muestra en la pantalla de pruebas
    public const CICLOS = [
        'antes' => ['Paga primero (comida rápida)', [
            ['Mesero', 'fa-bell-concierge', 'Toma el pedido con el nombre del cliente o la mesa'],
            ['Caja', 'fa-cash-register', 'Ve el pedido "por cobrar" y lo cobra en el POS'],
            ['Cocina / Bar', 'fa-fire-burner', 'Recién pagado le llega a cada estación lo suyo'],
            ['Cocina / Bar', 'fa-check', 'Marcan "listo" lo que prepararon'],
            ['Mesero', 'fa-bell', 'Le llega el aviso con sonido: "¡Listo para entregar!"'],
            ['Mesero', 'fa-hand-holding', 'Lo entrega y el pedido queda cerrado'],
        ]],
        'despues' => ['Paga al final (mesa)', [
            ['Mesero', 'fa-bell-concierge', 'Toma el pedido de la mesa'],
            ['Cocina / Bar', 'fa-fire-burner', 'Les llega de una para preparar'],
            ['Cocina / Bar', 'fa-check', 'Marcan "listo"'],
            ['Mesero', 'fa-bell', 'Le llega el aviso y lo entrega (puede pedir más rondas)'],
            ['Mesero', 'fa-receipt', 'El cliente pide la cuenta: pasa a caja'],
            ['Caja', 'fa-cash-register', 'Cobra en el POS y el pedido queda cerrado'],
        ]],
    ];

    public function ejecutar(string $modo = 'antes'): array
    {
        $modo = $modo === 'despues' ? 'despues' : 'antes';
        $admin = Auth::user();
        $tiendaAntes = TiendaActual::id();
        $reqAntes = app('request');
        $inicio = microtime(true);

        DB::beginTransaction();
        try {
            $this->preparar($modo);
            $modo === 'antes' ? $this->flujoPagaPrimero() : $this->flujoPagaAlFinal();
            $this->permisos();
        } catch (\Throwable $e) {
            // El paso que falló ya quedó anotado; si fue algo fuera de un paso, se anota aquí
            if (!$e instanceof PasoFallido) $this->pasos[] = ['n' => 'Error inesperado', 'ok' => false, 'd' => $this->texto($e), 'ms' => 0];
        } finally {
            DB::rollBack();
            Auth::setUser($admin);
            TiendaActual::set($tiendaAntes);
            app()->instance('request', $reqAntes);
            session()->forget('success');
        }

        return [
            'modo' => $modo, 'titulo' => self::CICLOS[$modo][0], 'pasos' => $this->pasos,
            'ok' => collect($this->pasos)->every('ok') && count($this->pasos) > 0,
            'segundos' => round(microtime(true) - $inicio, 1),
        ];
    }

    // ===== Preparación: restaurante nuevo, roles, personal, carta y caja =====
    private function preparar(string $modo): void
    {
        $this->paso('Crear un restaurante nuevo, como lo haría un cliente', function () use ($modo) {
            [$t] = CrearTienda::crear(
                ['nombre' => 'Restaurante QA', 'pais' => 'CO', 'telefono' => '3000000000', 'tipo_negocio' => 'restaurante'],
                ['name' => 'Dueño QA', 'email' => 'qa-' . Str::lower(Str::random(10)) . '@prueba.localproo.com', 'password' => Str::random(20), 'telefono' => '3000000000']
            );
            // Igual que el registro: se encienden los módulos del tipo de negocio
            $t->forceFill(array_fill_keys(Tienda::MODULOS_POR_TIPO['restaurante'], true) + ['pedidos_cobro' => $modo])->save();
            $this->t = $t->fresh();
            TiendaActual::set($this->t->id);
            $this->ver($this->t->mod_pedidos, 'El módulo de pedidos no quedó encendido.');
            return "Negocio {$this->t->codigo}, cobro: " . ($modo === 'antes' ? 'paga primero' : 'paga al final');
        });

        $this->paso('Roles por defecto creados con sus permisos', function () {
            $roles = NegocioRol::orderBy('orden')->get()->keyBy('nombre');
            $esperados = collect(RolesNegocio::plantillasPorDefecto('restaurante'))->map(fn ($k) => RolesNegocio::PLANTILLAS[$k][0]);
            foreach ($esperados as $n) $this->ver(isset($roles[$n]), "Falta el rol \"$n\".");
            $this->ver($roles['Mesero']->tiene('pedidos-tomar') && !$roles['Mesero']->tiene('pedidos-cobrar'), 'El mesero debe tomar pedidos y no cobrar.');
            $this->ver($roles['Caja']->tiene('pedidos-cobrar') && $roles['Caja']->tiene('usar-pos'), 'Caja debe cobrar pedidos y usar el POS.');
            $this->ver($roles['Cocina']->tiene('pedidos-preparar') && !$roles['Cocina']->tiene('pedidos-tomar'), 'Cocina solo debe preparar.');
            $cocina = PedidoEstacion::where('nombre', 'Cocina')->first();
            $bar = PedidoEstacion::where('nombre', 'Bar y bebidas')->first();
            $this->ver($cocina && $bar, 'Faltan las estaciones Cocina y Bar y bebidas.');
            $this->ver($roles['Cocina']->estacionIds() === [$cocina->id], 'El rol Cocina debe ver solo la estación Cocina.');
            return $esperados->implode(', ');
        });

        $this->paso('Crear el personal y asignarle su rol', function () {
            $roles = NegocioRol::get()->keyBy('nombre');
            foreach (['mesero' => 'Mesero', 'caja' => 'Caja', 'cocina' => 'Cocina', 'bar' => 'Bar y bebidas'] as $k => $rol) {
                $u = User::create(['name' => ucfirst($k) . ' QA', 'email' => "qa-$k-" . Str::lower(Str::random(8)) . '@prueba.localproo.com', 'password' => Hash::make(Str::random(20)), 'tienda_id' => $this->t->id]);
                RolesNegocio::asignar($u, $roles[$rol]);
                $this->u[$k] = $u->fresh();
            }
            $this->ver($this->u['mesero']->can('pedidos-tomar') && !$this->u['mesero']->can('pedidos-cobrar'), 'El mesero quedó con permisos equivocados.');
            return 'Mesero, Caja, Cocina y Bar';
        });

        $this->paso('Carta: hamburguesa a cocina y limonada al bar', function () {
            $cat = CrearTienda::categoria('Platos');
            $est = PedidoEstacion::pluck('id', 'nombre');
            foreach (['hamburguesa' => ['Hamburguesa QA', 18000, 'Cocina'], 'limonada' => ['Limonada QA', 6000, 'Bar y bebidas']] as $k => [$n, $precio, $e]) {
                $p = Producto::create(['codigo' => CrearTienda::siguienteCodigo(), 'nombre' => $n, 'precio_venta' => $precio, 'controla_inventario' => false]);
                $p->forceFill(['estacion_id' => $est[$e]])->save();
                $p->categorias()->attach($cat->id);
                $this->prod[$k] = $p->variantes()->create(['talla' => null, 'color' => null, 'stock' => 0]);
            }
            return '2 productos';
        });

        $this->paso('Caja abre su turno', function () {
            Caja::create(['user_id' => $this->u['caja']->id, 'abierta_en' => now(), 'monto_inicial' => 0, 'estado' => 'abierta']);
            return 'Base $0';
        });
    }

    // ===== Paga primero =====
    private function flujoPagaPrimero(): void
    {
        $pedido = $this->tomarPedido();

        $this->paso('Cocina todavía no lo ve (falta pagar)', function () use ($pedido) {
            $this->ver(!$this->pedidoEnEstacion('cocina', $pedido), 'La cocina ya ve un pedido que no se ha pagado.');
            return 'Correcto';
        });

        $this->paso('Caja ve el pedido "por cobrar"', function () {
            $d = $this->json($this->llamar('caja', fn () => app(pedidosController::class)->cajaDatos()));
            $this->ver(($d['por_cobrar'] ?? 0) >= 1, 'La caja no ve el pedido por cobrar.');
            return $d['por_cobrar'] . ' por cobrar';
        });

        $this->cobrar($pedido, 'en_preparacion');
        $this->prepararYAvisar($pedido);

        $this->paso('Mesero marca "entregado" y el pedido se cierra', function () use ($pedido) {
            $this->llamar('mesero', fn () => app(pedidosController::class)->entregar($pedido->fresh()));
            $p = $pedido->fresh();
            $this->ver($p->estado === 'cerrado', "Quedó en \"{$p->estado_txt}\" y debía quedar cerrado.");
            $this->ver((bool) $p->entregado_at, 'No quedó la hora de entrega.');
            return 'Cerrado';
        });
    }

    // ===== Paga al final =====
    private function flujoPagaAlFinal(): void
    {
        $pedido = $this->tomarPedido();

        $this->paso('Cocina lo ve de una (paga al final)', function () use ($pedido) {
            $this->ver($pedido->fresh()->estado === 'en_preparacion', 'El pedido debía pasar a "Preparando".');
            $this->ver($this->pedidoEnEstacion('cocina', $pedido), 'La cocina no ve el pedido.');
            return 'Preparando';
        });

        $this->prepararYAvisar($pedido);

        $this->paso('Mesero entrega (todavía sin pagar)', function () use ($pedido) {
            $this->llamar('mesero', fn () => app(pedidosController::class)->entregar($pedido->fresh()));
            $p = $pedido->fresh();
            $this->ver($p->estado === 'entregado' && !$p->pagado, "Quedó en \"{$p->estado_txt}\" y debía quedar entregado sin pagar.");
            return 'Entregado';
        });

        $this->paso('Cliente pide la cuenta: pasa a caja', function () use ($pedido) {
            $this->llamar('mesero', fn () => app(pedidosController::class)->cuenta($pedido->fresh()));
            $p = $pedido->fresh();
            $this->ver($p->estado === 'por_cobrar', "Quedó en \"{$p->estado_txt}\" y debía quedar por cobrar.");
            return dinero($p->total);
        });

        $this->cobrar($pedido, 'cerrado');
    }

    // ===== Pasos compartidos =====
    private function tomarPedido(): Pedido
    {
        $pedido = null;
        $this->paso('Mesero toma el pedido (Juan, mesa 4)', function () use (&$pedido) {
            $d = $this->json($this->llamar('mesero', fn () => app(pedidosController::class)->store($this->req([
                'cliente_nombre' => 'Juan', 'mesa' => '4',
                'items' => [
                    ['variante_id' => $this->prod['hamburguesa']->id, 'cantidad' => 2, 'nota' => 'Sin cebolla'],
                    ['variante_id' => $this->prod['limonada']->id, 'cantidad' => 1],
                ],
            ]))));
            $this->ver(!empty($d['ok']), 'No se guardó el pedido.');
            $pedido = Pedido::latest('id')->first();
            $this->ver($pedido && (float) $pedido->total === 42000.0, 'El total debía ser $42.000.');
            return $pedido->titulo . ' · ' . dinero($pedido->total) . ' · ' . $pedido->estado_txt;
        });
        return $pedido;
    }

    private function cobrar(Pedido $pedido, string $estadoFinal): void
    {
        $this->paso('Caja cobra el pedido en el POS', function () use ($pedido, $estadoFinal) {
            $p = $pedido->fresh('items');
            $efectivo = MetodoPago::where('es_efectivo', true)->where('activo', true)->first();
            $this->ver((bool) $efectivo, 'No hay método de pago en efectivo.');
            $d = $this->json($this->llamar('caja', fn () => app(posController::class)->store($this->req([
                'items' => $p->items->where('estado', '<>', 'anulado')->map(fn ($i) => ['variante_id' => $i->producto_variante_id, 'cantidad' => (float) $i->cantidad])->values()->all(),
                'pagos' => [['metodo_id' => $efectivo->id, 'monto' => $p->total]],
                'pedido_id' => $p->id,
            ]))));
            $p = $pedido->fresh();
            $this->ver($p->pagado && $p->pos_venta_id, 'El pedido no quedó pagado.');
            $this->ver($p->estado === $estadoFinal, "Quedó en \"{$p->estado_txt}\" y debía quedar en \"" . (Pedido::ESTADOS[$estadoFinal][0] ?? $estadoFinal) . '".');
            return 'Venta ' . ($d['numero'] ?? $p->pos_venta_id) . ' · ' . $p->estado_txt;
        });
    }

    private function prepararYAvisar(Pedido $pedido): void
    {
        $this->paso('Cada estación recibe solo lo suyo', function () use ($pedido) {
            $c = $this->itemsEnEstacion('cocina', $pedido);
            $b = $this->itemsEnEstacion('bar', $pedido);
            $this->ver($c === ['Hamburguesa QA'], 'Cocina debía ver solo la hamburguesa (ve: ' . (implode(', ', $c) ?: 'nada') . ').');
            $this->ver($b === ['Limonada QA'], 'Bar debía ver solo la limonada (ve: ' . (implode(', ', $b) ?: 'nada') . ').');
            return 'Cocina: hamburguesa · Bar: limonada';
        });

        $this->paso('Cocina marca "listo" (falta el bar)', function () use ($pedido) {
            $this->marcar('cocina', $pedido);
            $p = $pedido->fresh();
            $this->ver($p->estado === 'en_preparacion', "Quedó en \"{$p->estado_txt}\": no debe avisar al mesero hasta que el bar termine.");
            return 'Sigue preparando';
        });

        $this->paso('Bar marca "listo": el pedido queda listo', function () use ($pedido) {
            $this->marcar('bar', $pedido);
            $p = $pedido->fresh();
            $this->ver($p->estado === 'listo' && $p->listo_at, "Quedó en \"{$p->estado_txt}\" y debía quedar listo para entregar.");
            return $p->estado_txt;
        });

        $this->paso('Al mesero le llega el aviso de "listo"', function () use ($pedido) {
            $d = $this->json($this->llamar('mesero', fn () => app(pedidosController::class)->meseroDatos($this->req([], 'GET'))));
            $mio = collect($d['pedidos'] ?? [])->firstWhere('id', $pedido->id);
            $this->ver($mio && $mio['estado'] === 'listo', 'El mesero no ve su pedido como listo.');
            return 'Aparece de primero en su pantalla (con sonido y vibración en el celular)';
        });
    }

    private function permisos(): void
    {
        $this->paso('Cada rol solo entra a lo suyo', function () {
            $this->ver($this->codigo('mesero', fn () => app(pedidosController::class)->cajaDatos()) === 403, 'El mesero pudo entrar a la caja.');
            $this->ver($this->codigo('cocina', fn () => app(pedidosController::class)->meseroDatos($this->req([], 'GET'))) === 403, 'La cocina pudo entrar a la pantalla del mesero.');
            $this->ver($this->codigo('mesero', fn () => app(pedidosController::class)->estacionDatos($this->req([], 'GET'))) === 403, 'El mesero pudo entrar a la cocina.');
            return 'Mesero ✗ caja · Cocina ✗ mesero · Mesero ✗ cocina';
        });
    }

    // ===== Ayudas =====
    private function marcar(string $quien, Pedido $pedido): void
    {
        $d = $this->json($this->llamar($quien, fn () => app(pedidosController::class)->estacionDatos($this->req([], 'GET'))));
        $g = collect($d['pedidos'] ?? [])->firstWhere('id', $pedido->id);
        $this->ver((bool) $g, ucfirst($quien) . ' no ve el pedido.');
        $this->llamar($quien, fn () => app(pedidosController::class)->marcar($this->req(['items' => collect($g['items'])->pluck('id')->all()])));
    }

    private function pedidoEnEstacion(string $quien, Pedido $pedido): bool
    {
        return (bool) $this->itemsEnEstacion($quien, $pedido);
    }

    private function itemsEnEstacion(string $quien, Pedido $pedido): array
    {
        $d = $this->json($this->llamar($quien, fn () => app(pedidosController::class)->estacionDatos($this->req([], 'GET'))));
        $g = collect($d['pedidos'] ?? [])->firstWhere('id', $pedido->id);
        return $g ? collect($g['items'])->pluck('n')->sort()->values()->all() : [];
    }

    // Ejecuta una pantalla como si la usara esa persona
    private function llamar(string $quien, callable $fn)
    {
        $u = $this->u[$quien]->fresh();
        Auth::setUser($u);
        TiendaActual::set($this->t->id);
        return $fn();
    }

    private function codigo(string $quien, callable $fn): int
    {
        try {
            $this->llamar($quien, $fn);
            return 200;
        } catch (HttpExceptionInterface $e) {
            return $e->getStatusCode();
        }
    }

    private function req(array $datos, string $metodo = 'POST'): Request
    {
        $r = Request::create('/prueba-sistema', $metodo, $datos);
        $r->setLaravelSession(session()->driver());
        $r->setUserResolver(fn () => Auth::user());
        app()->instance('request', $r);
        return $r;
    }

    private function json($resp): array
    {
        $this->ver($resp instanceof JsonResponse, 'La pantalla no respondió como se esperaba.');
        $this->ver($resp->getStatusCode() < 400, $resp->getData(true)['message'] ?? 'Error ' . $resp->getStatusCode());
        return $resp->getData(true);
    }

    private function ver($cond, string $msg): void
    {
        if (!$cond) throw new \RuntimeException($msg);
    }

    private function paso(string $nombre, callable $fn): void
    {
        $t0 = microtime(true);
        try {
            $d = $fn();
            $this->pasos[] = ['n' => $nombre, 'ok' => true, 'd' => (string) $d, 'ms' => (int) round((microtime(true) - $t0) * 1000)];
        } catch (\Throwable $e) {
            $this->pasos[] = ['n' => $nombre, 'ok' => false, 'd' => $this->texto($e), 'ms' => (int) round((microtime(true) - $t0) * 1000)];
            throw new PasoFallido($nombre, 0, $e);
        }
    }

    private function texto(\Throwable $e): string
    {
        if ($e instanceof ValidationException) return collect($e->errors())->flatten()->implode(' ');
        if ($e instanceof HttpExceptionInterface) return 'Error ' . $e->getStatusCode() . ': ' . ($e->getMessage() ?: 'sin permiso');
        $m = $e->getMessage();
        return $e instanceof \RuntimeException && !$e instanceof \UnexpectedValueException && get_class($e) === \RuntimeException::class
            ? $m
            : class_basename($e) . ': ' . Str::limit($m, 300) . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
    }
}
