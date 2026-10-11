<?php

use App\Http\Controllers\adminTiendaController;
use App\Http\Controllers\bienvenidaController;
use App\Http\Controllers\cajaController;
use App\Http\Controllers\categoriaController;
use App\Http\Controllers\clienteController;
use App\Http\Controllers\devolucionController;
use App\Http\Controllers\entradaController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\inventarioController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\logoutController;
use App\Http\Controllers\metodoPagoController;
use App\Http\Controllers\negocioController;
use App\Http\Controllers\posController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\profileController;
use App\Http\Controllers\proveedorController;
use App\Http\Controllers\recuperarController;
use App\Http\Controllers\registroController;
use App\Http\Controllers\reporteController;
use App\Http\Controllers\userController;
use App\Http\Controllers\varianteController;
use Illuminate\Support\Facades\Route;

/*
| LocalPro — plataforma POS para muchas tiendas.
| Cada tienda solo ve sus propios datos (ver App\Http\Middleware\ResolverTienda).
*/

// ===== Público =====
Route::get('/', [homeController::class, 'index'])->name('panel');
Route::get('/login', [loginController::class, 'index'])->name('login');
Route::post('/login', [loginController::class, 'login'])->middleware('throttle:10,1');
Route::get('/logout', [logoutController::class, 'logout'])->name('logout');
Route::get('/registro', [registroController::class, 'index'])->name('registro');
Route::post('/registro', [registroController::class, 'store'])->middleware('throttle:5,1');
Route::get('/recuperar', [recuperarController::class, 'formulario'])->name('password.request');
Route::post('/recuperar', [recuperarController::class, 'enviar'])->middleware('throttle:5,1')->name('password.email');
Route::get('/restablecer/{token}', [recuperarController::class, 'formularioNueva'])->name('password.reset');
Route::post('/restablecer', [recuperarController::class, 'guardarNueva'])->middleware('throttle:10,1')->name('password.update');
Route::get('/factura/{venta}', [posController::class, 'facturaPublica'])->name('factura.publica');

// ===== Asistente de configuración de la tienda nueva =====
Route::middleware(['auth', 'tienda:asistente'])->prefix('bienvenida')->name('bienvenida.')->group(function () {
    Route::get('/', [bienvenidaController::class, 'index'])->name('index');
    Route::post('/negocio', [bienvenidaController::class, 'negocio'])->name('negocio');
    Route::post('/productos', [bienvenidaController::class, 'productos'])->name('productos');
    Route::post('/cobro', [bienvenidaController::class, 'cobro'])->name('cobro');
    Route::get('/paso/{paso}', [bienvenidaController::class, 'irAPaso'])->whereNumber('paso')->name('paso');
});

// ===== Pantallas de la tienda =====
Route::middleware(['auth', 'tienda'])->group(function () {
    Route::resources([
        'categorias' => categoriaController::class,
        'productos' => ProductoController::class,
        'clientes' => clienteController::class,
        'proveedores' => proveedorController::class,
        'users' => userController::class,
        'profile' => profileController::class,
    ]);

    // POS
    Route::get('/pos', [posController::class, 'index'])->name('pos.index');
    Route::get('/pos/buscar', [posController::class, 'buscar'])->name('pos.buscar');
    Route::post('/pos/ventas', [posController::class, 'store'])->name('pos.store');
    Route::get('/pos/ventas', [posController::class, 'ventas'])->name('pos.ventas');
    Route::get('/pos/ventas/{venta}/ticket', [posController::class, 'ticket'])->name('pos.ticket');
    Route::post('/pos/ventas/{venta}/anular', [posController::class, 'anular'])->name('pos.anular');
    Route::get('/pos/ventas/{venta}/devolucion', [devolucionController::class, 'create'])->name('devoluciones.create');
    Route::post('/pos/ventas/{venta}/devolucion', [devolucionController::class, 'store'])->name('devoluciones.store');
    Route::get('/devoluciones/{devolucion}', [devolucionController::class, 'show'])->name('devoluciones.show');
    Route::get('/pos/clientes', [posController::class, 'clientes'])->name('pos.clientes');
    Route::get('/pos/saldo', [devolucionController::class, 'saldo'])->name('pos.saldo');

    // Caja
    Route::get('/cajas', [cajaController::class, 'index'])->name('cajas.index');
    Route::get('/cajas/abrir', [cajaController::class, 'abrir'])->name('cajas.abrir');
    Route::post('/cajas/abrir', [cajaController::class, 'guardarApertura'])->name('cajas.guardarApertura');
    Route::get('/cajas/actual', [cajaController::class, 'actual'])->name('cajas.actual');
    Route::get('/cajas/cerrar', [cajaController::class, 'cerrar'])->name('cajas.cerrar');
    Route::post('/cajas/cerrar', [cajaController::class, 'guardarCierre'])->name('cajas.guardarCierre');
    Route::get('/cajas/{caja}', [cajaController::class, 'show'])->name('cajas.show');
    Route::get('/cajas/{caja}/reporte', [cajaController::class, 'reporte'])->name('cajas.reporte');

    // Entradas de mercancía
    Route::resource('entradas', entradaController::class)->only(['index', 'create', 'store', 'show']);

    // Inventario, tallas y colores
    Route::get('/inventario', [inventarioController::class, 'index'])->name('inventario.index');
    Route::get('/inventario/movimientos', [inventarioController::class, 'movimientos'])->name('inventario.movimientos');
    Route::get('/productos/{producto}/variantes', [varianteController::class, 'index'])->name('productos.variantes');
    Route::post('/productos/{producto}/variantes', [varianteController::class, 'store'])->name('variantes.store');
    Route::put('/variantes/{variante}', [varianteController::class, 'update'])->name('variantes.update');
    Route::post('/variantes/{variante}/ajustar', [varianteController::class, 'ajustar'])->name('variantes.ajustar');
    Route::delete('/variantes/{variante}', [varianteController::class, 'destroy'])->name('variantes.destroy');

    // Reportes
    Route::get('/reportes', [reporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar/{tipo}', [reporteController::class, 'exportar'])->whereIn('tipo', ['ventas', 'productos', 'inventario'])->name('reportes.exportar');

    // Configuración del negocio
    Route::get('/metodos-pago', [metodoPagoController::class, 'index'])->name('metodos-pago.index');
    Route::post('/metodos-pago', [metodoPagoController::class, 'store'])->name('metodos-pago.store');
    Route::put('/metodos-pago/{metodo}', [metodoPagoController::class, 'update'])->name('metodos-pago.update');
    Route::get('/negocio', [negocioController::class, 'edit'])->name('negocio.edit');
    Route::put('/negocio', [negocioController::class, 'update'])->name('negocio.update');
});

// ===== Administrador de la plataforma =====
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/tiendas', [adminTiendaController::class, 'index'])->name('tiendas.index');
    Route::get('/tiendas/nueva', [adminTiendaController::class, 'create'])->name('tiendas.create');
    Route::post('/tiendas', [adminTiendaController::class, 'store'])->name('tiendas.store');
    Route::get('/tiendas/{tienda}', [adminTiendaController::class, 'show'])->name('tiendas.show');
    Route::post('/tiendas/{tienda}/estado', [adminTiendaController::class, 'estado'])->name('tiendas.estado');
    Route::post('/tiendas/{tienda}/entrar', [adminTiendaController::class, 'entrar'])->name('tiendas.entrar');
    Route::post('/salir-de-tienda', [adminTiendaController::class, 'salir'])->name('tiendas.salir');
});

Route::get('/401', fn () => view('pages.401'));
Route::get('/404', fn () => view('pages.404'));
Route::get('/500', fn () => view('pages.500'));
