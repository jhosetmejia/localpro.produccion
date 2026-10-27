<?php

use App\Http\Controllers\eventoPublicoController;
use App\Http\Controllers\eventosController;
use Illuminate\Support\Facades\Route;

// ===== LocalProo Eventos: página del evento, registro, pago por Nequi y entrada con QR =====

// Lo que ve el público (sin iniciar sesión en LocalProo).
// Ojo: el límite "throttle" cuenta por IP para todas las rutas juntas; en un colegio muchos comparten la misma IP (wifi),
// por eso las páginas que solo se ven no llevan límite y los envíos tienen límites amplios.
Route::controller(eventoPublicoController::class)->group(function () {
    Route::get('/eventos/terminos', 'terminos')->name('eventos.terminos-compra');
    Route::get('/evento/{slug}', 'show')->where('slug', '[a-z0-9-]+')->name('evento.publico');
    Route::get('/evento/{slug}/comprar', 'comprar')->where('slug', '[a-z0-9-]+')->name('evento.comprar');
    Route::post('/evento/{slug}/comprar', 'registrar')->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('evento.registrar');
    Route::get('/evento/{slug}/pago', 'pago')->where('slug', '[a-z0-9-]+')->name('evento.pago');
    Route::post('/evento/{slug}/comprobante', 'aviso')->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('evento.aviso');

    Route::get('/mis-entradas', 'index')->name('mis-entradas.index');
    Route::get('/mis-entradas/ingresar', 'ingresar')->name('mis-entradas.ingresar');
    Route::post('/mis-entradas/ingresar', 'login')->middleware('throttle:30,1')->name('mis-entradas.login');
    Route::post('/mis-entradas/salir', 'salir')->name('mis-entradas.salir');
    Route::get('/mis-entradas/{uuid}', 'ver')->whereUuid('uuid')->name('mis-entradas.ver');
});

// Panel del negocio
Route::middleware(['auth', 'tienda'])->prefix('eventos')->name('eventos.')->controller(eventosController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/activar', 'activar')->name('activar');
    Route::get('/nuevo', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{evento}', 'show')->whereNumber('evento')->name('show');
    Route::get('/{evento}/editar', 'edit')->whereNumber('evento')->name('edit');
    Route::put('/{evento}', 'update')->whereNumber('evento')->name('update');
    Route::delete('/{evento}', 'destroy')->whereNumber('evento')->name('destroy');
    Route::get('/{evento}/exportar', 'exportar')->whereNumber('evento')->name('exportar');

    Route::post('/{evento}/entradas/{entrada}/aprobar', 'aprobar')->whereNumber(['evento', 'entrada'])->name('aprobar');
    Route::post('/{evento}/entradas/{entrada}/rechazar', 'rechazar')->whereNumber(['evento', 'entrada'])->name('rechazar');
    Route::post('/{evento}/entradas/{entrada}/pendiente', 'pendiente')->whereNumber(['evento', 'entrada'])->name('pendiente');
    Route::post('/{evento}/entradas/{entrada}/clave', 'clave')->whereNumber(['evento', 'entrada'])->name('clave');

    // Puerta: escanear el QR
    Route::get('/{evento}/puerta', 'puerta')->whereNumber('evento')->name('puerta');
    Route::post('/{evento}/puerta/validar', 'validar')->whereNumber('evento')->middleware('throttle:300,1')->name('validar');
});
