<?php

use App\Http\Controllers\nominaController;
use Illuminate\Support\Facades\Route;

// ===== Nómina (opcional por negocio) y nómina electrónica DIAN =====
Route::middleware(['auth', 'tienda'])->prefix('nomina')->name('nomina.')->controller(nominaController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/modo', 'modo')->name('modo');
    Route::post('/configuracion', 'config')->name('config');

    Route::get('/empleados', 'empleados')->name('empleados');
    Route::get('/empleados/nuevo', 'empleadoForm')->name('empleados.create');
    Route::post('/empleados', 'empleadoGuardar')->name('empleados.store');
    Route::get('/empleados/{empleado}/editar', 'empleadoForm')->whereNumber('empleado')->name('empleados.edit');
    Route::put('/empleados/{empleado}', 'empleadoGuardar')->whereNumber('empleado')->name('empleados.update');

    Route::post('/periodos', 'periodoCrear')->name('periodos.store');
    Route::get('/periodos/{periodo}', 'periodo')->whereNumber('periodo')->name('periodo');
    Route::put('/periodos/{periodo}', 'periodoGuardar')->whereNumber('periodo')->name('periodo.update');
    Route::post('/periodos/{periodo}/estado', 'periodoEstado')->whereNumber('periodo')->name('periodo.estado');
    Route::delete('/periodos/{periodo}', 'periodoBorrar')->whereNumber('periodo')->name('periodo.destroy');
    Route::get('/periodos/{periodo}/desprendibles/{liquidacion?}', 'desprendible')->whereNumber(['periodo', 'liquidacion'])->name('desprendible');

    Route::get('/electronica', 'electronica')->name('electronica');
    Route::post('/electronica/generar', 'electronicaGenerar')->middleware('throttle:10,1')->name('electronica.generar');
    Route::get('/electronica/{documento}', 'electronicaVer')->whereNumber('documento')->name('electronica.ver');
    Route::post('/electronica/{documento}/enviar', 'electronicaEnviar')->whereNumber('documento')->middleware('throttle:30,1')->name('electronica.enviar');
    Route::post('/electronica/{documento}/ajuste', 'electronicaAjuste')->whereNumber('documento')->middleware('throttle:20,1')->name('electronica.ajuste');
});
