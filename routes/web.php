<?php

use App\Http\Controllers\importarController;
use App\Http\Controllers\adminImportacionController;
use App\Http\Controllers\adminPlanController;
use App\Http\Controllers\adminRedController;
use App\Http\Controllers\adminTiendaController;
use App\Http\Controllers\asesorPanelController;
use App\Http\Controllers\aspiranteController;
use App\Http\Controllers\bienvenidaController;
use App\Http\Controllers\cajaController;
use App\Http\Controllers\catalogoController;
use App\Http\Controllers\cuentaController;
use App\Http\Controllers\cursoController;
use App\Http\Controllers\conjuntoController;
use App\Http\Controllers\adminInicioController;
use App\Http\Controllers\categoriaController;
use App\Http\Controllers\clienteController;
use App\Http\Controllers\devolucionController;
use App\Http\Controllers\entradaController;
use App\Http\Controllers\etiquetaController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\inventarioController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\logoutController;
use App\Http\Controllers\metodoPagoController;
use App\Http\Controllers\negocioController;
use App\Http\Controllers\planController;
use App\Http\Controllers\posController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\profileController;
use App\Http\Controllers\proveedorController;
use App\Http\Controllers\recuperarController;
use App\Http\Controllers\registroController;
use App\Http\Controllers\reporteController;
use App\Http\Controllers\userController;
use App\Http\Controllers\varianteController;
use App\Http\Controllers\unirseController;
use App\Http\Controllers\wompiController;
use App\Http\Controllers\pagarController;
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
Route::get('/t/{slug}', [catalogoController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('catalogo.show');
Route::get('/t/{slug}/p/{producto}', [catalogoController::class, 'producto'])->where(['slug' => '[a-z0-9-]+', 'producto' => '[0-9]+(-[a-z0-9-]*)?'])->name('catalogo.producto');
Route::get('/sitemap.xml', [catalogoController::class, 'sitemap'])->name('sitemap');
Route::post('/webhooks/wompi', [wompiController::class, 'evento'])->middleware('throttle:60,1');
// Enlace de pago de un negocio (firmado): lo usa el dueño o su asesor para pagar el plan con Wompi sin entrar al sistema
Route::get('/pagar/retorno', [pagarController::class, 'retorno'])->name('pagar.retorno');
Route::get('/pagar/{tienda}', [pagarController::class, 'show'])->whereNumber('tienda')->middleware('signed:relative')->name('pagar.show');
Route::post('/pagar/{tienda}/{plan}', [pagarController::class, 'ir'])->whereNumber(['tienda', 'plan'])->middleware(['signed:relative', 'throttle:10,1'])->name('pagar.ir');
Route::get('/factura/{venta}', [posController::class, 'facturaPublica'])->name('factura.publica');
// Enlace de un asesor: el negocio que se inscribe queda en su red
Route::get('/r/{codigo}', [registroController::class, 'referido'])->where('codigo', '[A-Za-z0-9\-]+')->name('referido');

// Enlace para sumar asesores a la red: se inscribe, hace un curso corto y al aprobarlo queda como asesor
Route::get('/unete/{codigo}', [aspiranteController::class, 'form'])->where('codigo', '[A-Za-z0-9\-]+')->name('unete.form');
Route::post('/unete/{codigo}', [aspiranteController::class, 'store'])->where('codigo', '[A-Za-z0-9\-]+')->middleware('throttle:5,1');

// Curso básico del aspirante a asesor y su certificado
Route::middleware('auth')->prefix('curso')->name('curso.')->group(function () {
    Route::get('/', [cursoController::class, 'index'])->name('index');
    Route::get('/leccion/{n}', [cursoController::class, 'leccion'])->whereNumber('n')->name('leccion');
    Route::post('/leccion/{n}', [cursoController::class, 'completar'])->whereNumber('n')->name('completar');
    Route::get('/examen', [cursoController::class, 'examen'])->name('examen');
    Route::post('/examen', [cursoController::class, 'calificar'])->middleware('throttle:20,1')->name('calificar');
    Route::get('/certificado', [cursoController::class, 'certificado'])->name('certificado');
    Route::post('/reiniciar', [cursoController::class, 'reiniciar'])->name('reiniciar');
});
Route::get('/certificado/{codigo}', [cursoController::class, 'verificar'])->where('codigo', '[A-Za-z0-9\-]+')->name('certificado.verificar');

// ===== Panel de la red comercial (asesores y líderes) =====
Route::middleware(['auth', 'asesor'])->prefix('asesor')->name('asesor.')->group(function () {
    Route::get('/', [asesorPanelController::class, 'index'])->name('index');
    Route::get('/red', [asesorPanelController::class, 'red'])->name('red');
    Route::get('/comisiones', [asesorPanelController::class, 'comisiones'])->name('comisiones');
    Route::post('/aspirantes/{aspirante}', [aspiranteController::class, 'decidir'])->name('aspirantes.decidir');
    Route::post('/equipo/{aspirante}/desactivar', [aspiranteController::class, 'desactivar'])->name('equipo.desactivar');
});
// Mi cuenta (todos los usuarios): datos y clave
Route::middleware('auth')->group(function () {
    Route::get('/cuenta', [cuentaController::class, 'edit'])->name('cuenta.edit');
    Route::put('/cuenta', [cuentaController::class, 'update'])->name('cuenta.update');
});
// Simulador de ganancias para reclutar: solo asesores, líderes y el administrador
Route::middleware('auth')->get('/asesor/simulador', [unirseController::class, 'index'])->name('asesor.simulador');
Route::middleware('auth')->get('/asesor/capacitacion', [unirseController::class, 'capacitacion'])->name('asesor.capacitacion');

// ===== Asistente de configuración de la tienda nueva =====
Route::middleware(['auth', 'tienda:asistente'])->prefix('bienvenida')->name('bienvenida.')->group(function () {
    Route::get('/', [bienvenidaController::class, 'index'])->name('index');
    Route::post('/negocio', [bienvenidaController::class, 'negocio'])->name('negocio');
    Route::post('/productos', [bienvenidaController::class, 'productos'])->name('productos');
    Route::post('/cobro', [bienvenidaController::class, 'cobro'])->name('cobro');
    Route::get('/paso/{paso}', [bienvenidaController::class, 'irAPaso'])->whereNumber('paso')->name('paso');
});

// ===== Administración de conjuntos residenciales =====
Route::middleware(['auth', 'tienda', 'conjunto'])->prefix('conjunto')->name('conjunto.')->controller(conjuntoController::class)->group(function () {
    Route::get('/', 'inicio')->name('inicio');
    Route::get('/unidades', 'unidades')->name('unidades');
    Route::post('/unidades', 'unidadGuardar')->name('unidades.store');
    Route::post('/unidades/lote', 'unidadesLote')->name('unidades.lote');
    Route::get('/unidades/{unidad}', 'unidad')->name('unidad');
    Route::put('/unidades/{unidad}', 'unidadGuardar')->name('unidades.update');
    Route::post('/unidades/{unidad}/pago', 'pagoGuardar')->name('pago.store');
    Route::get('/cobros', 'cobros')->name('cobros');
    Route::post('/cobros/mes', 'generarMes')->name('cobros.mes');
    Route::post('/cobros/especial', 'cargoEspecial')->name('cobros.especial');
    Route::post('/cobros/intereses', 'generarIntereses')->name('cobros.intereses');
    Route::post('/cobros/{cargo}/anular', 'cargoAnular')->name('cobros.anular');
    Route::get('/pagos', 'pagos')->name('pagos');
    Route::get('/pagos/{pago}', 'recibo')->name('recibo');
    Route::post('/pagos/{pago}/anular', 'pagoAnular')->name('pago.anular');
    Route::get('/cartera', 'cartera')->name('cartera');
    Route::get('/gastos', 'gastos')->name('gastos');
    Route::post('/gastos', 'gastoGuardar')->name('gastos.store');
    Route::delete('/gastos/{gasto}', 'gastoBorrar')->name('gastos.destroy');
    Route::get('/comunicados', 'comunicados')->name('comunicados');
    Route::post('/comunicados', 'comunicadoGuardar')->name('comunicados.store');
    Route::get('/pqrs', 'pqrs')->name('pqrs');
    Route::post('/pqrs', 'pqrsGuardar')->name('pqrs.store');
    Route::put('/pqrs/{pqrs}', 'pqrsGuardar')->name('pqrs.update');
    Route::get('/reservas', 'reservas')->name('reservas');
    Route::post('/reservas', 'reservaGuardar')->name('reservas.store');
    Route::post('/reservas/{reserva}/cancelar', 'reservaCancelar')->name('reservas.cancelar');
    Route::post('/configuracion', 'config')->name('config');
});

// ===== Pantallas de la tienda =====
Route::middleware(['auth', 'tienda'])->group(function () {
    // Paso "comparte tu tienda": se marca hecho y abre WhatsApp con el enlace de la tienda en línea
    Route::get('/compartir-tienda', function () {
        $t = \App\Support\TiendaActual::tienda();
        \App\Models\Ajuste::guardar('t' . $t->id . '_compartio', now()->toDateTimeString());
        $msg = '¡Hola! 👋 Le comparto la página de ' . $t->nombre . ': vea nuestros productos y compre por WhatsApp 👉 ' . $t->url_catalogo;
        return redirect()->away('https://wa.me/?text=' . rawurlencode($msg));
    })->name('panel.compartir');
    Route::post('/equipo/{user}/meta', [\App\Http\Controllers\metaController::class, 'guardar'])->name('equipo.meta');

    // Importar productos desde Excel (va antes de productos/{producto})
    Route::get('/productos/importar', [importarController::class, 'index'])->name('productos.importar');
    Route::get('/productos/importar/plantilla', [importarController::class, 'plantilla'])->name('productos.importar.plantilla');
    Route::post('/productos/importar', [importarController::class, 'subir'])->name('productos.importar.subir');
    Route::get('/productos/importar/revisar', [importarController::class, 'revisar'])->name('productos.importar.revisar');
    Route::post('/productos/importar/confirmar', [importarController::class, 'confirmar'])->name('productos.importar.confirmar');
    Route::post('/productos/importar/cancelar', [importarController::class, 'cancelar'])->name('productos.importar.cancelar');
    Route::post('/productos/importar/equipo', [importarController::class, 'solicitar'])->name('productos.importar.solicitar');

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
    Route::post('/pos/clientes', [posController::class, 'crearCliente'])->name('pos.clientes.crear');
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

    // Etiquetas con código de barras
    Route::get('/productos/{producto}/etiquetas', [etiquetaController::class, 'producto'])->name('etiquetas.producto');
    Route::get('/entradas/{entrada}/etiquetas', [etiquetaController::class, 'entrada'])->name('etiquetas.entrada');

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

    // Plan de LocalPro
    Route::get('/mi-plan', [planController::class, 'index'])->name('plan.index');
    Route::post('/mi-plan/{plan}/pagar', [planController::class, 'pagar'])->name('plan.pagar');
    Route::get('/mi-plan/retorno', [planController::class, 'retorno'])->name('plan.retorno');

    // Configuración del negocio
    Route::get('/metodos-pago', [metodoPagoController::class, 'index'])->name('metodos-pago.index');
    Route::post('/metodos-pago', [metodoPagoController::class, 'store'])->name('metodos-pago.store');
    Route::put('/metodos-pago/{metodo}', [metodoPagoController::class, 'update'])->name('metodos-pago.update');
    Route::get('/negocio', [negocioController::class, 'edit'])->name('negocio.edit');
    Route::put('/negocio', [negocioController::class, 'update'])->name('negocio.update');
});

// ===== Administrador de la plataforma =====
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [adminInicioController::class, 'index'])->name('inicio');
    Route::get('/usuarios', [adminInicioController::class, 'usuarios'])->name('usuarios');
    Route::post('/usuarios/{user}/clave', [adminInicioController::class, 'clave'])->name('usuarios.clave');
    Route::get('/pagos', [adminInicioController::class, 'pagos'])->name('pagos');
    Route::get('/tiendas', [adminTiendaController::class, 'index'])->name('tiendas.index');
    Route::get('/tiendas/nueva', [adminTiendaController::class, 'create'])->name('tiendas.create');
    Route::post('/tiendas', [adminTiendaController::class, 'store'])->name('tiendas.store');
    Route::get('/tiendas/{tienda}', [adminTiendaController::class, 'show'])->name('tiendas.show');
    Route::post('/tiendas/{tienda}/estado', [adminTiendaController::class, 'estado'])->name('tiendas.estado');
    Route::post('/tiendas/{tienda}/entrar', [adminTiendaController::class, 'entrar'])->name('tiendas.entrar');
    Route::post('/tiendas/{tienda}/plan', [adminTiendaController::class, 'plan'])->name('tiendas.plan');
    Route::post('/tiendas/{tienda}/pago', [adminTiendaController::class, 'pago'])->name('tiendas.pago');
    Route::get('/planes', [adminPlanController::class, 'index'])->name('planes.index');
    Route::post('/planes', [adminPlanController::class, 'guardar'])->name('planes.guardar');
    Route::post('/salir-de-tienda', [adminTiendaController::class, 'salir'])->name('tiendas.salir');
    Route::post('/vista', [adminTiendaController::class, 'vista'])->name('vista');
    Route::get('/importaciones', [adminImportacionController::class, 'index'])->name('importaciones');
    Route::get('/importaciones/{importacion}/archivo', [adminImportacionController::class, 'descargar'])->name('importaciones.archivo');
    Route::post('/importaciones/{importacion}/atender', [adminImportacionController::class, 'atender'])->name('importaciones.atender');
    Route::post('/importaciones/{importacion}/estado', [adminImportacionController::class, 'estado'])->name('importaciones.estado');
    Route::get('/red', [adminRedController::class, 'index'])->name('red.index');
    Route::post('/red', [adminRedController::class, 'store'])->name('red.store');
    Route::get('/red/comisiones', [adminRedController::class, 'comisiones'])->name('red.comisiones');
    Route::post('/red/comisiones/pagar', [adminRedController::class, 'pagar'])->name('red.pagar');
    Route::post('/red/comisiones/{comision}/anular', [adminRedController::class, 'anular'])->name('red.anular');
    Route::post('/red/valores', [adminRedController::class, 'valores'])->name('red.valores');
    Route::post('/red/{asesor}/ascender', [adminRedController::class, 'ascender'])->name('red.ascender');
    Route::get('/red/{asesor}', [adminRedController::class, 'show'])->name('red.show');
    Route::put('/red/{asesor}', [adminRedController::class, 'update'])->name('red.update');
    Route::post('/tiendas/{tienda}/asesor', [adminRedController::class, 'asignar'])->name('tiendas.asesor');
});

Route::get('/401', fn () => view('pages.401'));
Route::get('/404', fn () => view('pages.404'));
Route::get('/500', fn () => view('pages.500'));
