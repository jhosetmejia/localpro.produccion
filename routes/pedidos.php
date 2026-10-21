<?php

use App\Http\Controllers\negocioRolController;
use App\Http\Controllers\pedidosController;
use Illuminate\Support\Facades\Route;

// ===== Pedidos de restaurante y roles del negocio =====

// Pantalla para los clientes "Su pedido está listo" (sin iniciar sesión, con dirección secreta)
Route::controller(pedidosController::class)->middleware('throttle:240,1')->group(function () {
    Route::get('/pedidos-listos/{token}', 'pantallaClientes')->where('token', '[a-z0-9]{20,40}')->name('pedidos.clientes');
    Route::get('/pedidos-listos/{token}/datos', 'pantallaClientesDatos')->where('token', '[a-z0-9]{20,40}')->name('pedidos.clientes.datos');
});

Route::middleware(['auth', 'tienda'])->prefix('pedidos')->name('pedidos.')->controller(pedidosController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/guia', 'guia')->name('guia');
    Route::get('/mesero', 'mesero')->name('mesero');
    Route::get('/mesero/datos', 'meseroDatos')->name('mesero.datos');
    Route::get('/nuevo', 'crear')->name('crear');
    Route::post('/', 'store')->name('store');
    Route::get('/caja', 'caja')->name('caja');
    Route::get('/caja/datos', 'cajaDatos')->name('caja.datos');
    Route::get('/estacion', 'estacion')->name('estacion');
    Route::get('/estacion/datos', 'estacionDatos')->name('estacion.datos');
    Route::post('/estacion/marcar', 'marcar')->name('estacion.marcar');
    Route::get('/configurar', 'configurar')->name('configurar');
    Route::post('/configurar', 'guardarConfig')->name('configurar.guardar');
    Route::post('/configurar/productos', 'asignarProductos')->name('configurar.productos');
    Route::delete('/estaciones/{estacion}', 'borrarEstacion')->whereNumber('estacion')->name('estaciones.destroy');
    Route::get('/cierre', 'cierre')->name('cierre');
    Route::post('/items/{item}/quitar', 'anularItem')->whereNumber('item')->name('items.quitar');
    Route::get('/{pedido}', 'show')->whereNumber('pedido')->name('show');
    Route::get('/{pedido}/agregar', 'crear')->whereNumber('pedido')->name('agregar');
    Route::get('/{pedido}/comanda', 'comanda')->whereNumber('pedido')->name('comanda');
    Route::post('/{pedido}/entregar', 'entregar')->whereNumber('pedido')->name('entregar');
    Route::post('/{pedido}/cuenta', 'cuenta')->whereNumber('pedido')->name('cuenta');
    Route::post('/{pedido}/anular', 'anular')->whereNumber('pedido')->name('anular');
});

Route::middleware(['auth', 'tienda'])->prefix('roles-del-negocio')->name('roles-negocio.')->controller(negocioRolController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/nuevo', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::post('/plantilla', 'plantilla')->name('plantilla');
    Route::post('/asignar', 'asignar')->name('asignar');
    Route::get('/{rol}', 'edit')->whereNumber('rol')->name('edit');
    Route::put('/{rol}', 'update')->whereNumber('rol')->name('update');
    Route::delete('/{rol}', 'destroy')->whereNumber('rol')->name('destroy');
});
