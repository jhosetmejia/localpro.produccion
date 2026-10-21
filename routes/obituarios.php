<?php

use App\Http\Controllers\obituariosController;
use Illuminate\Support\Facades\Route;

// ===== Obituarios (funerarias): salas de velación, plantillas y pantallas =====

// Pantallas de los televisores (sin iniciar sesión, con dirección secreta)
Route::controller(obituariosController::class)->middleware('throttle:240,1')->group(function () {
    Route::get('/pantalla/{token}', 'pantalla')->where('token', '[a-z0-9]{20,40}')->name('obituarios.pantalla');
    Route::get('/pantalla/{token}/version', 'version')->where('token', '[a-z0-9]{20,40}')->name('obituarios.version');
    Route::get('/pantalla-general/{token}', 'pantallaGeneral')->where('token', '[a-z0-9]{20,40}')->name('obituarios.general');
    Route::get('/pantalla-general/{token}/version', 'versionGeneral')->where('token', '[a-z0-9]{20,40}')->name('obituarios.general.version');
});

Route::middleware(['auth', 'tienda'])->prefix('obituarios')->name('obituarios.')->controller(obituariosController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/activar', 'activar')->name('activar');
    Route::get('/nuevo', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{obituario}', 'show')->whereNumber('obituario')->name('show');
    Route::get('/{obituario}/editar', 'edit')->whereNumber('obituario')->name('edit');
    Route::put('/{obituario}', 'update')->whereNumber('obituario')->name('update');
    Route::post('/{obituario}/estado', 'estado')->whereNumber('obituario')->name('estado');
    Route::post('/{obituario}/ver-ya', 'verYa')->whereNumber('obituario')->name('ver-ya');
    Route::delete('/{obituario}', 'destroy')->whereNumber('obituario')->name('destroy');

    // Salas de velación
    Route::post('/salas', 'salaStore')->name('salas.store');
    Route::put('/salas/{sala}', 'salaUpdate')->whereNumber('sala')->name('salas.update');
    Route::delete('/salas/{sala}', 'salaDestroy')->whereNumber('sala')->name('salas.destroy');
    Route::post('/pantalla-general', 'generalConfig')->name('general.config');

    // Plantillas (diseño, medida, fondo, video, animación)
    Route::get('/plantillas', 'plantillas')->name('plantillas');
    Route::get('/plantillas/nueva', 'plantillaForm')->name('plantillas.create');
    Route::post('/plantillas', 'plantillaGuardar')->name('plantillas.store');
    Route::get('/plantillas/{plantilla}/editar', 'plantillaForm')->whereNumber('plantilla')->name('plantillas.edit');
    Route::put('/plantillas/{plantilla}', 'plantillaGuardar')->whereNumber('plantilla')->name('plantillas.update');
    Route::post('/plantillas/{plantilla}/duplicar', 'plantillaDuplicar')->whereNumber('plantilla')->name('plantillas.duplicar');
    Route::delete('/plantillas/{plantilla}', 'plantillaBorrar')->whereNumber('plantilla')->name('plantillas.destroy');
});
