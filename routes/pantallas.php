<?php

use App\Http\Controllers\pantallasController;
use Illuminate\Support\Facades\Route;

// ===== Pantallas del negocio: imágenes y videos en los televisores, sin cables ni USB =====

// Televisor (sin iniciar sesión, dirección secreta). El código corto entra por localproo.com/tv/1234
Route::controller(pantallasController::class)->middleware('throttle:240,1')->group(function () {
    Route::get('/pantallas/tv/{token}', 'ver')->where('token', '[a-z0-9]{20,40}')->name('pantallas.ver');
    Route::get('/pantallas/tv/{token}/version', 'version')->where('token', '[a-z0-9]{20,40}')->name('pantallas.version');
});

Route::middleware(['auth', 'tienda'])->prefix('pantallas')->name('pantallas.')->controller(pantallasController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/activar', 'activar')->name('activar');
    Route::post('/', 'store')->name('store');
    Route::get('/{pantalla}', 'show')->whereNumber('pantalla')->name('show');
    Route::put('/{pantalla}', 'update')->whereNumber('pantalla')->name('update');
    Route::delete('/{pantalla}', 'destroy')->whereNumber('pantalla')->name('destroy');
    Route::post('/{pantalla}/archivos', 'itemStore')->whereNumber('pantalla')->name('items.store');
    Route::put('/archivos/{item}', 'itemUpdate')->whereNumber('item')->name('items.update');
    Route::post('/archivos/{item}/mover', 'itemMover')->whereNumber('item')->name('items.mover');
    Route::delete('/archivos/{item}', 'itemDestroy')->whereNumber('item')->name('items.destroy');
});
