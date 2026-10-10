<?php

use App\Http\Controllers\cajaController;
use App\Http\Controllers\categoriaController;
use App\Http\Controllers\inventarioController;
use App\Http\Controllers\metodoPagoController;
use App\Http\Controllers\negocioController;
use App\Http\Controllers\posController;
use App\Http\Controllers\varianteController;
use App\Http\Controllers\clienteController;
use App\Http\Controllers\compraController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\logoutController;
use App\Http\Controllers\marcaController;
use App\Http\Controllers\presentacioneController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\profileController;
use App\Http\Controllers\proveedorController;
use App\Http\Controllers\registroController;
use App\Http\Controllers\roleController;
use App\Http\Controllers\userController;
use App\Http\Controllers\ventaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/',[homeController::class,'index'])->name('panel');

Route::resources([
    'categorias' => categoriaController::class,
    'presentaciones' => presentacioneController::class,
    'marcas' => marcaController::class,
    'productos' => ProductoController::class,
    'clientes' => clienteController::class,
    'proveedores' => proveedorController::class,
    'compras' => compraController::class,
    'ventas' => ventaController::class,
    'users' => userController::class,
    'roles' => roleController::class,
    'profile' => profileController::class
]);

// ===== POS tienda de ropa =====
Route::get('/pos', [posController::class, 'index'])->name('pos.index');
Route::get('/pos/buscar', [posController::class, 'buscar'])->name('pos.buscar');
Route::post('/pos/ventas', [posController::class, 'store'])->name('pos.store');
Route::get('/pos/ventas', [posController::class, 'ventas'])->name('pos.ventas');
Route::get('/pos/ventas/{venta}/ticket', [posController::class, 'ticket'])->name('pos.ticket');
Route::post('/pos/ventas/{venta}/anular', [posController::class, 'anular'])->name('pos.anular');
Route::get('/factura/{venta}', [posController::class, 'facturaPublica'])->name('factura.publica');

Route::get('/cajas', [cajaController::class, 'index'])->name('cajas.index');
Route::get('/cajas/abrir', [cajaController::class, 'abrir'])->name('cajas.abrir');
Route::post('/cajas/abrir', [cajaController::class, 'guardarApertura'])->name('cajas.guardarApertura');
Route::get('/cajas/actual', [cajaController::class, 'actual'])->name('cajas.actual');
Route::get('/cajas/cerrar', [cajaController::class, 'cerrar'])->name('cajas.cerrar');
Route::post('/cajas/cerrar', [cajaController::class, 'guardarCierre'])->name('cajas.guardarCierre');
Route::get('/cajas/{caja}', [cajaController::class, 'show'])->name('cajas.show');
Route::get('/cajas/{caja}/reporte', [cajaController::class, 'reporte'])->name('cajas.reporte');

Route::get('/inventario', [inventarioController::class, 'index'])->name('inventario.index');
Route::get('/inventario/movimientos', [inventarioController::class, 'movimientos'])->name('inventario.movimientos');
Route::get('/productos/{producto}/variantes', [varianteController::class, 'index'])->name('productos.variantes');
Route::post('/productos/{producto}/variantes', [varianteController::class, 'store'])->name('variantes.store');
Route::put('/variantes/{variante}', [varianteController::class, 'update'])->name('variantes.update');
Route::post('/variantes/{variante}/ajustar', [varianteController::class, 'ajustar'])->name('variantes.ajustar');
Route::delete('/variantes/{variante}', [varianteController::class, 'destroy'])->name('variantes.destroy');

Route::get('/metodos-pago', [metodoPagoController::class, 'index'])->name('metodos-pago.index');
Route::post('/metodos-pago', [metodoPagoController::class, 'store'])->name('metodos-pago.store');
Route::put('/metodos-pago/{metodo}', [metodoPagoController::class, 'update'])->name('metodos-pago.update');

Route::get('/negocio', [negocioController::class, 'edit'])->name('negocio.edit');
Route::put('/negocio', [negocioController::class, 'update'])->name('negocio.update');

Route::get('/login',[loginController::class,'index'])->name('login');
Route::post('/login',[loginController::class,'login'])->middleware('throttle:10,1');
Route::get('/registro',[registroController::class,'index'])->name('registro');
Route::post('/registro',[registroController::class,'store'])->middleware('throttle:5,1');
Route::get('/logout',[logoutController::class,'logout'])->name('logout');

Route::get('/401', function () {
    return view('pages.401');
});
Route::get('/404', function () {
    return view('pages.404');
});
Route::get('/500', function () {
    return view('pages.500');
});
