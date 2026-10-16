<?php

use App\Http\Controllers\contabilidadController;
use Illuminate\Support\Facades\Route;

// ===== Contabilidad: retenciones e IVA para analistas contables =====
Route::middleware(['auth', 'contable'])->prefix('contabilidad')->name('contabilidad.')->controller(contabilidadController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/activar', 'activar')->name('activar');
    Route::get('/guia', 'guia')->name('guia');
    Route::get('/calendario', 'calendario')->name('calendario');
    Route::post('/ejemplo', 'ejemplo')->name('ejemplo');
    Route::get('/ejemplo/{archivo}', 'ejemploArchivo')->whereIn('archivo', ['dian', 'contable'])->name('ejemplo.archivo');

    Route::post('/clientes', 'clienteStore')->name('clientes.store');
    Route::get('/clientes/{cliente}', 'cliente')->whereNumber('cliente')->name('cliente');
    Route::put('/clientes/{cliente}', 'clienteUpdate')->whereNumber('cliente')->name('clientes.update');
    Route::delete('/clientes/{cliente}', 'clienteDestroy')->whereNumber('cliente')->name('clientes.destroy');
    Route::post('/clientes/{cliente}/meses', 'periodoStore')->whereNumber('cliente')->name('periodos.store');
    Route::get('/clientes/{cliente}/certificado', 'certificado')->whereNumber('cliente')->name('certificado');

    Route::get('/meses/{periodo}', 'periodo')->whereNumber('periodo')->name('periodo');
    Route::post('/meses/{periodo}/dian', 'subirDian')->whereNumber('periodo')->name('dian');
    Route::post('/meses/{periodo}/contable', 'subirContable')->whereNumber('periodo')->name('contable');
    Route::get('/meses/{periodo}/columnas', 'mapeo')->whereNumber('periodo')->name('mapeo');
    Route::post('/meses/{periodo}/columnas', 'mapeoGuardar')->whereNumber('periodo')->name('mapeo.guardar');
    Route::delete('/meses/{periodo}/carga/{origen}', 'borrarCarga')->whereNumber('periodo')->whereIn('origen', ['dian', 'contable'])->name('carga.borrar');
    Route::post('/meses/{periodo}/concepto', 'concepto')->whereNumber('periodo')->name('concepto');
    Route::put('/meses/{periodo}', 'periodoDatos')->whereNumber('periodo')->name('periodo.datos');
    Route::delete('/meses/{periodo}', 'periodoDestroy')->whereNumber('periodo')->name('periodo.destroy');
    Route::get('/meses/{periodo}/excel', 'exportar')->whereNumber('periodo')->name('exportar');

    Route::put('/terceros/{tercero}', 'tercero')->whereNumber('tercero')->name('tercero');

    Route::get('/tarifas', 'tarifas')->name('tarifas');
    Route::post('/tarifas', 'tarifasGuardar')->name('tarifas.guardar');
    Route::post('/tarifas/restaurar', 'tarifasRestaurar')->name('tarifas.restaurar');
});
