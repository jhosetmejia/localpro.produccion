<?php

use App\Http\Controllers\importarController;
use App\Http\Controllers\importarClientesController;
use App\Http\Controllers\separadoController;
use App\Http\Controllers\alquilerController;
use App\Http\Controllers\redesController;
use App\Http\Controllers\metaWebhookController;
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
// Enlace corto para contadores: abre el registro con el tipo "Contador / analista contable" y la herramienta activa
Route::get('/contadores', fn () => redirect()->route('registro', ['tipo' => 'contable'] + request()->only('desde')))->name('registro.contadores');
Route::get('/contadores/{codigo}', [registroController::class, 'referidoContador'])->where('codigo', '[A-Za-z0-9\-]+')->name('registro.contadores.asesor');
Route::post('/registro', [registroController::class, 'store'])->middleware('throttle:5,1');
Route::get('/recuperar', [recuperarController::class, 'formulario'])->name('password.request');
Route::post('/recuperar', [recuperarController::class, 'enviar'])->middleware('throttle:5,1')->name('password.email');
Route::get('/restablecer/{token}', [recuperarController::class, 'formularioNueva'])->name('password.reset');
Route::post('/restablecer', [recuperarController::class, 'guardarNueva'])->middleware('throttle:10,1')->name('password.update');
Route::get('/t/{slug}', [catalogoController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('catalogo.show');
Route::get('/t/{slug}/yo', [catalogoController::class, 'yo'])->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('catalogo.yo');
Route::get('/t/{slug}/p/{producto}', [catalogoController::class, 'producto'])->where(['slug' => '[a-z0-9-]+', 'producto' => '[0-9]+(-[a-z0-9-]*)?'])->name('catalogo.producto');
Route::get('/sitemap.xml', [catalogoController::class, 'sitemap'])->name('sitemap');
Route::post('/webhooks/wompi', [wompiController::class, 'evento'])->middleware('throttle:60,1');
Route::get('/webhooks/meta', [metaWebhookController::class, 'verificar']);
// Páginas legales (las pide Meta para aprobar la app)
foreach (['privacidad' => 'privacidad', 'terminos' => 'terminos', 'eliminar-datos' => 'eliminar'] as $ruta => $vista) {
    Route::get('/' . $ruta, fn () => view('legal.' . $vista, ['correo' => \App\Models\Ajuste::valor('correo_legal') ?: config('mail.from.address')]))->name('legal.' . $vista);
}
// Mis enlaces: página tipo Linktree de cada negocio (localproo.com/@negocio)
Route::get('/@{slug}', [\App\Http\Controllers\enlacesController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('enlaces.publico');
Route::get('/@{slug}/ir/{enlace}', [\App\Http\Controllers\enlacesController::class, 'ir'])->where(['slug' => '[a-z0-9-]+', 'enlace' => '[0-9]+'])->middleware('throttle:120,1')->name('enlaces.ir');
Route::get('/l/{slug}', fn (string $slug) => redirect('/@' . $slug, 301))->where('slug', '[a-z0-9-]+');

// Blog público (artículos para compartir con negocios y asesores)
Route::get('/blog', fn () => view('blog.index', ['articulos' => config('blog')]))->name('blog.index');
// Páginas del blog por tipo de cliente (contadores, funerarias, negocios): atraen búsquedas y llevan al registro con el tipo elegido
Route::get('/blog/para/{rol}', function (string $rol) {
    $r = config('blog_roles.' . $rol);
    abort_unless($r, 404);
    $articulos = collect(config('blog'))->filter(fn ($a) => ($a['rol'] ?? 'negocios') === $rol)->all();
    return view('blog.rol', ['r' => $r, 'rolKey' => $rol, 'articulos' => $articulos]);
})->where('rol', '[a-z]+')->name('blog.rol');
Route::get('/blog/{slug}', function (string $slug) {
    $a = config('blog.' . $slug);
    abort_unless($a, 404);
    return view('blog.' . $slug, ['a' => $a, 'slug' => $slug]);
})->where('slug', '[a-z0-9-]+')->name('blog.show');
Route::post('/webhooks/meta', [metaWebhookController::class, 'evento'])->middleware('throttle:600,1');
// Enlace de pago de un negocio (firmado): lo usa el dueño o su asesor para pagar el plan con Wompi sin entrar al sistema
Route::get('/pagar/retorno', [pagarController::class, 'retorno'])->name('pagar.retorno');
// Estado de un separado para el cliente (enlace firmado que se envía por WhatsApp)
Route::get('/mi-separado/{separado}', [separadoController::class, 'publico'])->whereNumber('separado')->middleware('signed:relative')->name('separados.publico');
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
    Route::get('/resultado', [cursoController::class, 'resultado'])->name('resultado');
    Route::get('/certificado', [cursoController::class, 'certificado'])->name('certificado');
    Route::post('/reiniciar', [cursoController::class, 'reiniciar'])->name('reiniciar');
});
// Resultado de la prueba básica que el aspirante le comparte a su reclutador (código del asesor, ej. LCP-00012)
Route::get('/prueba/{codigo}', [cursoController::class, 'verPrueba'])->where('codigo', '[A-Za-z0-9\-]+')->name('prueba.ver');
// El código siempre empieza con letra (CERT-00001); así no se cruza con /certificado/{nivel} del panel
Route::get('/certificado/{codigo}', [cursoController::class, 'verificar'])->where('codigo', '[A-Za-z][A-Za-z0-9\-]*')->name('certificado.verificar');

// ===== Panel de la red comercial (asesores y líderes) =====
Route::middleware(['auth', 'asesor'])->prefix('asesor')->name('asesor.')->group(function () {
    Route::get('/', [asesorPanelController::class, 'index'])->name('index');
    Route::get('/red', [asesorPanelController::class, 'red'])->name('red');
    Route::get('/comisiones', [asesorPanelController::class, 'comisiones'])->name('comisiones');
    Route::get('/vender', [\App\Http\Controllers\asesorVenderController::class, 'index'])->name('vender');
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
Route::middleware('auth')->get('/asesor/contactos', [\App\Http\Controllers\asesorContactosController::class, 'index'])->name('asesor.contactos');
Route::middleware('auth')->post('/asesor/contactos', [\App\Http\Controllers\asesorContactosController::class, 'subir'])->name('asesor.contactos.subir');
Route::middleware('auth')->post('/asesor/contactos/{contacto}/marcar', [\App\Http\Controllers\asesorContactosController::class, 'marcar'])->whereNumber('contacto')->name('asesor.contactos.marcar');
Route::middleware('auth')->post('/asesor/contactos/red/{contacto}/marcar', [\App\Http\Controllers\asesorContactosController::class, 'marcarRed'])->whereNumber('contacto')->name('asesor.contactos.red.marcar');
Route::middleware('auth')->get('/asesor/capacitacion', [unirseController::class, 'capacitacion'])->name('asesor.capacitacion');
Route::middleware('auth')->get('/asesor/ejemplos', [\App\Http\Controllers\asesorEjemplosController::class, 'index'])->name('asesor.ejemplos');

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
// "Aprende LocalPro": cursos para el dueño, su equipo y los asesores
Route::middleware('auth')->prefix('aprende')->name('aprende.')->group(function () {
    Route::get('/', [\App\Http\Controllers\academiaController::class, 'index'])->name('index');
    Route::get('/{curso}', [\App\Http\Controllers\academiaController::class, 'curso'])->where('curso', '[a-z0-9-]+')->name('curso');
    Route::post('/{curso}/leccion/{n}', [\App\Http\Controllers\academiaController::class, 'leccion'])->where('curso', '[a-z0-9-]+')->whereNumber('n')->name('leccion');
    Route::post('/{curso}/prueba', [\App\Http\Controllers\academiaController::class, 'prueba'])->where('curso', '[a-z0-9-]+')->middleware('throttle:30,1')->name('prueba');
});

Route::middleware(['auth', 'tienda'])->group(function () {
    // Paso "comparte tu tienda": se marca hecho y abre WhatsApp con el enlace de la tienda en línea
    Route::get('/compartir-tienda', function () {
        $t = \App\Support\TiendaActual::tienda();
        \App\Models\Ajuste::guardar('t' . $t->id . '_compartio', now()->toDateTimeString());
        $msg = '¡Hola! 👋 Le comparto la página de ' . $t->nombre . ': vea nuestros productos y compre por WhatsApp 👉 ' . $t->url_catalogo;
        return redirect()->away('https://wa.me/?text=' . rawurlencode($msg));
    })->name('panel.compartir');
    Route::get('/certificado/{nivel}', function (int $nivel) {
        $nv = \App\Support\Niveles::calcular();
        abort_unless($nivel >= 1 && $nivel <= $nv['logrados'], 404);
        return view('panel.certificado', ['nivel' => $nivel, 'tienda' => $nv['tienda'], 'nombre' => \App\Support\Niveles::nombre($nivel)]);
    })->whereNumber('nivel')->name('panel.certificado');
    Route::post('/equipo/{user}/meta', [\App\Http\Controllers\metaController::class, 'guardar'])->name('equipo.meta');

    // Importar productos desde Excel (va antes de productos/{producto})
    Route::get('/productos/importar', [importarController::class, 'index'])->name('productos.importar');
    Route::get('/productos/importar/plantilla', [importarController::class, 'plantilla'])->name('productos.importar.plantilla');
    Route::post('/productos/importar', [importarController::class, 'subir'])->name('productos.importar.subir');
    Route::get('/productos/importar/revisar', [importarController::class, 'revisar'])->name('productos.importar.revisar');
    Route::post('/productos/importar/confirmar', [importarController::class, 'confirmar'])->name('productos.importar.confirmar');
    Route::post('/productos/importar/cancelar', [importarController::class, 'cancelar'])->name('productos.importar.cancelar');
    Route::post('/productos/importar/equipo', [importarController::class, 'solicitar'])->name('productos.importar.solicitar');

    Route::get('/clientes/importar', [importarClientesController::class, 'index'])->name('clientes.importar');
    Route::get('/vender-mas', [\App\Http\Controllers\venderMasController::class, 'index'])->name('vender.index');
    Route::get('/clientes/contactar', [\App\Http\Controllers\contactarController::class, 'index'])->name('clientes.contactar');
    Route::post('/clientes/contactar/mas', [\App\Http\Controllers\contactarController::class, 'mas'])->name('clientes.contactar.mas');
    Route::post('/clientes/contactar/ajustes', [\App\Http\Controllers\contactarController::class, 'ajustes'])->name('clientes.contactar.ajustes');
    Route::post('/clientes/{cliente}/contactado', [\App\Http\Controllers\contactarController::class, 'marcar'])->whereNumber('cliente')->name('clientes.contactado');
    Route::get('/clientes/{cliente}/contactado', [\App\Http\Controllers\contactarController::class, 'revisar'])->whereNumber('cliente')->name('clientes.contactado.revisar');
    Route::get('/clientes/crear-base', [importarClientesController::class, 'contactos'])->name('clientes.contactos');
    Route::get('/clientes/importar/plantilla', [importarClientesController::class, 'plantilla'])->name('clientes.importar.plantilla');
    Route::post('/clientes/importar', [importarClientesController::class, 'subir'])->name('clientes.importar.subir');
    Route::get('/clientes/importar/revisar', [importarClientesController::class, 'revisar'])->name('clientes.importar.revisar');
    Route::post('/clientes/importar/confirmar', [importarClientesController::class, 'confirmar'])->name('clientes.importar.confirmar');
    Route::post('/clientes/importar/cancelar', [importarClientesController::class, 'cancelar'])->name('clientes.importar.cancelar');

    // Permisos extra de cada vendedor (los da el dueño)
    Route::get('/users/{user}/permisos', [userController::class, 'permisos'])->whereNumber('user')->name('users.permisos');
    Route::put('/users/{user}/permisos', [userController::class, 'guardarPermisos'])->whereNumber('user')->name('users.permisos.guardar');

    Route::delete('/productos/{producto}/eliminar', [ProductoController::class, 'eliminar'])->whereNumber('producto')->name('productos.eliminar');
    Route::delete('/categorias/{categoria}/eliminar', [categoriaController::class, 'eliminar'])->whereNumber('categoria')->name('categorias.eliminar');
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
    Route::get('/separados', [separadoController::class, 'index'])->name('separados.index');

    // Alquileres: servicios que el negocio pone en alquiler
    Route::get('/alquileres', [alquilerController::class, 'index'])->name('alquileres.index');
    Route::get('/alquileres/nuevo', [alquilerController::class, 'create'])->name('alquileres.create');
    Route::post('/alquileres', [alquilerController::class, 'store'])->name('alquileres.store');
    Route::get('/alquileres/{alquiler}/editar', [alquilerController::class, 'edit'])->whereNumber('alquiler')->name('alquileres.edit');
    Route::put('/alquileres/{alquiler}', [alquilerController::class, 'update'])->whereNumber('alquiler')->name('alquileres.update');
    Route::post('/alquileres/{alquiler}/cobrar', [alquilerController::class, 'cobrar'])->whereNumber('alquiler')->name('alquileres.cobrar');
    Route::post('/alquileres/{alquiler}/abonar', [alquilerController::class, 'abonar'])->whereNumber('alquiler')->name('alquileres.abonar');
    Route::match(['post', 'put'], '/alquileres/{alquiler}/corregir-pago', [alquilerController::class, 'corregirPago'])->whereNumber('alquiler')->name('alquileres.corregirPago');
    Route::post('/alquileres/{alquiler}/entrega', [alquilerController::class, 'entrega'])->whereNumber('alquiler')->name('alquileres.entrega');
    Route::get('/alquileres/{alquiler}/documento', [alquilerController::class, 'documento'])->whereNumber('alquiler')->name('alquileres.documento');
    Route::post('/alquileres/{alquiler}/estado', [alquilerController::class, 'estado'])->whereNumber('alquiler')->name('alquileres.estado');
    Route::delete('/alquileres/{alquiler}', [alquilerController::class, 'destroy'])->whereNumber('alquiler')->name('alquileres.destroy');

    // Mis redes (Facebook e Instagram)
    Route::get('/mis-redes', [redesController::class, 'index'])->name('redes.index');
    Route::get('/mis-redes/chats', [redesController::class, 'lista'])->name('redes.lista');
    Route::get('/mis-redes/chats/{conversacion}', [redesController::class, 'mensajes'])->whereNumber('conversacion')->name('redes.mensajes');
    Route::post('/mis-redes/chats/{conversacion}/enviar', [redesController::class, 'enviar'])->whereNumber('conversacion')->middleware('throttle:60,1')->name('redes.enviar');
    Route::post('/mis-redes/chats/{conversacion}/etiqueta', [redesController::class, 'etiqueta'])->whereNumber('conversacion')->name('redes.etiqueta');
    Route::post('/mis-redes/chats/{conversacion}/cliente', [redesController::class, 'guardarCliente'])->whereNumber('conversacion')->name('redes.cliente');
    Route::post('/mis-redes/actualizar', [redesController::class, 'sincronizar'])->middleware('throttle:10,1')->name('redes.sincronizar');
    Route::get('/mis-redes/productos', [redesController::class, 'productos'])->name('redes.productos');
    Route::get('/mis-redes/publicar', [redesController::class, 'publicar'])->name('redes.publicar');
    Route::post('/mis-redes/publicar', [redesController::class, 'guardarPublicacion'])->middleware('throttle:20,1')->name('redes.publicar.guardar');
    Route::post('/mis-redes/publicaciones/{publicacion}/reintentar', [redesController::class, 'reintentar'])->whereNumber('publicacion')->name('redes.reintentar');
    Route::delete('/mis-redes/publicaciones/{publicacion}', [redesController::class, 'borrarPublicacion'])->whereNumber('publicacion')->name('redes.publicacion.borrar');
    Route::get('/mis-redes/cuentas', [redesController::class, 'cuentasVista'])->name('redes.cuentas');
    Route::get('/mis-redes/conectar', [redesController::class, 'conectar'])->name('redes.conectar');
    Route::get('/mis-redes/conectar/retorno', [redesController::class, 'callback'])->name('redes.callback');
    Route::get('/mis-redes/conectar/paginas', [redesController::class, 'elegir'])->name('redes.elegir');
    Route::post('/mis-redes/conectar/paginas', [redesController::class, 'guardarPaginas'])->name('redes.elegir.guardar');
    Route::post('/mis-redes/cuentas/{cuenta}/desconectar', [redesController::class, 'desconectar'])->whereNumber('cuenta')->name('redes.desconectar');
    Route::get('/separados/nuevo', [separadoController::class, 'create'])->name('separados.create');
    Route::post('/separados', [separadoController::class, 'store'])->name('separados.store');
    Route::get('/separados/{separado}', [separadoController::class, 'show'])->whereNumber('separado')->name('separados.show');
    Route::post('/separados/{separado}/abonar', [separadoController::class, 'abonar'])->whereNumber('separado')->name('separados.abonar');
    Route::post('/separados/{separado}/entregar', [separadoController::class, 'entregar'])->whereNumber('separado')->name('separados.entregar');
    Route::post('/separados/{separado}/cancelar', [separadoController::class, 'cancelar'])->whereNumber('separado')->name('separados.cancelar');
    Route::get('/separados/abonos/{abono}/recibo', [separadoController::class, 'recibo'])->whereNumber('abono')->name('separados.recibo');
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
    Route::post('/pos/clientes/{cliente}/datos', [posController::class, 'actualizarCliente'])->whereNumber('cliente')->name('pos.clientes.datos');
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
    Route::get('/etiquetas', [etiquetaController::class, 'todas'])->name('etiquetas.todas');
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
    Route::get('/mi-plan/vencido', [planController::class, 'vencido'])->name('plan.vencido');
    Route::post('/mi-plan/{plan}/pagar', [planController::class, 'pagar'])->name('plan.pagar');
    Route::get('/mi-plan/retorno', [planController::class, 'retorno'])->name('plan.retorno');

    // Configuración del negocio
    Route::get('/metodos-pago', [metodoPagoController::class, 'index'])->name('metodos-pago.index');
    Route::post('/metodos-pago', [metodoPagoController::class, 'store'])->name('metodos-pago.store');
    Route::put('/metodos-pago/{metodo}', [metodoPagoController::class, 'update'])->name('metodos-pago.update');
    // Mis enlaces (página tipo Linktree del negocio)
    Route::prefix('mis-enlaces')->name('enlaces.')->controller(\App\Http\Controllers\enlacesController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/perfil', 'perfil')->name('perfil');
        Route::post('/', 'store')->name('store');
        Route::put('/{enlace}', 'update')->whereNumber('enlace')->name('update');
        Route::post('/{enlace}/alternar', 'alternar')->whereNumber('enlace')->name('alternar');
        Route::post('/{enlace}/mover/{dir}', 'mover')->whereNumber('enlace')->whereIn('dir', ['arriba', 'abajo'])->name('mover');
        Route::delete('/{enlace}', 'destroy')->whereNumber('enlace')->name('destroy');
        Route::post('/estadisticas/reiniciar', 'reiniciar')->name('reiniciar');
        Route::post('/publicar', 'publicar')->name('publicar');
    });
    // Personalizar menú (esconder lo que el negocio no usa)
    Route::get('/negocio/menu', [\App\Http\Controllers\menuController::class, 'edit'])->name('menu.edit');
    Route::put('/negocio/menu', [\App\Http\Controllers\menuController::class, 'update'])->name('menu.update');
    Route::get('/primeros-pasos', [homeController::class, 'primerosPasos'])->name('primeros.pasos');
    Route::get('/negocio', [negocioController::class, 'edit'])->name('negocio.edit');
    Route::put('/negocio', [negocioController::class, 'update'])->name('negocio.update');
    // Facturación electrónica DIAN
    Route::get('/facturacion', [\App\Http\Controllers\facturacionController::class, 'index'])->name('facturacion.index');
    Route::put('/facturacion', [\App\Http\Controllers\facturacionController::class, 'update'])->name('facturacion.update');
    Route::post('/facturacion/revisar', [\App\Http\Controllers\facturacionController::class, 'revisar'])->middleware('throttle:20,1')->name('facturacion.revisar');
    Route::post('/facturacion/impuesto', [\App\Http\Controllers\facturacionController::class, 'impuestoMasivo'])->name('facturacion.impuesto');
    Route::get('/facturacion/documentos/{documento}', [\App\Http\Controllers\facturacionController::class, 'documento'])->whereNumber('documento')->name('facturacion.documento');
    Route::post('/facturacion/documentos/{documento}/reintentar', [\App\Http\Controllers\facturacionController::class, 'reintentar'])->whereNumber('documento')->middleware('throttle:30,1')->name('facturacion.reintentar');
});

// ===== Administrador de la plataforma =====
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [adminInicioController::class, 'index'])->name('inicio');
    Route::get('/usuarios', [adminInicioController::class, 'usuarios'])->name('usuarios');
    Route::post('/usuarios/{user}/clave', [adminInicioController::class, 'clave'])->name('usuarios.clave');
    Route::get('/pagos', [adminInicioController::class, 'pagos'])->name('pagos');
    Route::post('/pagos/verificar', [adminInicioController::class, 'verificarPagos'])->name('pagos.verificar');
    Route::get('/tiendas', [adminTiendaController::class, 'index'])->name('tiendas.index');
    Route::get('/tiendas/nueva', [adminTiendaController::class, 'create'])->name('tiendas.create');
    Route::post('/tiendas', [adminTiendaController::class, 'store'])->name('tiendas.store');
    Route::get('/tiendas/{tienda}', [adminTiendaController::class, 'show'])->name('tiendas.show');
    Route::post('/tiendas/{tienda}/estado', [adminTiendaController::class, 'estado'])->name('tiendas.estado');
    Route::post('/tiendas/{tienda}/entrar', [adminTiendaController::class, 'entrar'])->name('tiendas.entrar');
    Route::post('/tiendas/{tienda}/plan', [adminTiendaController::class, 'plan'])->name('tiendas.plan');
    Route::post('/tiendas/{tienda}/pago', [adminTiendaController::class, 'pago'])->name('tiendas.pago');
    Route::post('/tiendas/{tienda}/productos-extra', [adminTiendaController::class, 'productosExtra'])->name('tiendas.productosExtra');
    Route::delete('/tiendas/{tienda}', [adminTiendaController::class, 'eliminar'])->name('tiendas.eliminar');
    Route::post('/tiendas/{tienda}/pago-prueba', [adminTiendaController::class, 'pagoPrueba'])->name('tiendas.pagoPrueba');
    Route::post('/tiendas/{tienda}/ventas-historicas', [adminTiendaController::class, 'ventasHistoricas'])->name('tiendas.ventasHistoricas');
    Route::get('/planes', [adminPlanController::class, 'index'])->name('planes.index');
    Route::post('/planes', [adminPlanController::class, 'guardar'])->name('planes.guardar');
    Route::post('/salir-de-tienda', [adminTiendaController::class, 'salir'])->name('tiendas.salir');
    Route::post('/vista', [adminTiendaController::class, 'vista'])->name('vista');
    Route::get('/importaciones', [adminImportacionController::class, 'index'])->name('importaciones');
    Route::get('/auditoria', [adminInicioController::class, 'auditoria'])->name('auditoria');
    Route::get('/facturacion', [\App\Http\Controllers\adminFacturacionController::class, 'index'])->name('facturacion');
    Route::post('/facturacion', [\App\Http\Controllers\adminFacturacionController::class, 'guardar'])->name('facturacion.guardar');
    Route::post('/facturacion/cola', [\App\Http\Controllers\adminFacturacionController::class, 'enviarCola'])->middleware('throttle:6,1')->name('facturacion.cola');
    Route::post('/auditoria', [adminInicioController::class, 'correrAuditoria'])->middleware('throttle:6,1')->name('auditoria.correr');
    Route::get('/importaciones/{importacion}/archivo', [adminImportacionController::class, 'descargar'])->name('importaciones.archivo');
    Route::post('/importaciones/{importacion}/atender', [adminImportacionController::class, 'atender'])->name('importaciones.atender');
    Route::post('/importaciones/{importacion}/estado', [adminImportacionController::class, 'estado'])->name('importaciones.estado');
    Route::get('/red', [adminRedController::class, 'index'])->name('red.index');
    Route::post('/red', [adminRedController::class, 'store'])->name('red.store');
    // Base de contactos de LocalPro para todos los asesores (a cada persona se le escribe una sola vez)
    Route::get('/red/contactos', [\App\Http\Controllers\adminRedContactosController::class, 'index'])->name('red.contactos');
    Route::post('/red/contactos', [\App\Http\Controllers\adminRedContactosController::class, 'subir'])->name('red.contactos.subir');
    Route::post('/red/contactos/ajustes', [\App\Http\Controllers\adminRedContactosController::class, 'ajustes'])->name('red.contactos.ajustes');
    Route::post('/red/contactos/{contacto}/liberar', [\App\Http\Controllers\adminRedContactosController::class, 'liberar'])->whereNumber('contacto')->name('red.contactos.liberar');
    Route::get('/red/comisiones', [adminRedController::class, 'comisiones'])->name('red.comisiones');
    Route::post('/red/comisiones/pagar', [adminRedController::class, 'pagar'])->name('red.pagar');
    Route::post('/red/comisiones/{comision}/anular', [adminRedController::class, 'anular'])->name('red.anular');
    Route::post('/red/valores', [adminRedController::class, 'valores'])->name('red.valores');
    Route::post('/red/{asesor}/ascender', [adminRedController::class, 'ascender'])->name('red.ascender');
    Route::get('/red/{asesor}', [adminRedController::class, 'show'])->name('red.show');
    Route::put('/red/{asesor}', [adminRedController::class, 'update'])->name('red.update');
    Route::post('/tiendas/{tienda}/asesor', [adminRedController::class, 'asignar'])->name('tiendas.asesor');
    // Prospección automática (firmas contables): envío diario, lista y resultados
    Route::get('/prospectos', [\App\Http\Controllers\adminProspectosController::class, 'index'])->name('prospectos');
    Route::post('/prospectos/ajustes', [\App\Http\Controllers\adminProspectosController::class, 'guardar'])->name('prospectos.guardar');
    Route::post('/prospectos/agregar', [\App\Http\Controllers\adminProspectosController::class, 'agregar'])->name('prospectos.agregar');
    Route::post('/prospectos/prueba', [\App\Http\Controllers\adminProspectosController::class, 'prueba'])->middleware('throttle:6,1')->name('prospectos.prueba');
    Route::post('/prospectos/correr', [\App\Http\Controllers\adminProspectosController::class, 'correr'])->middleware('throttle:6,1')->name('prospectos.correr');
    Route::post('/prospectos/{prospecto}/estado', [\App\Http\Controllers\adminProspectosController::class, 'estado'])->name('prospectos.estado');
});

// Enlace de baja de los correos comerciales (un clic; también POST para el botón "Cancelar suscripción" del correo)
Route::match(['get', 'post'], '/baja/{token}', [\App\Http\Controllers\adminProspectosController::class, 'baja'])->where('token', '[A-Za-z0-9]{10,40}')->middleware('throttle:30,1')->name('prospectos.baja');

Route::get('/401', fn () => view('pages.401'));
Route::get('/404', fn () => view('pages.404'));
Route::get('/500', fn () => view('pages.500'));

// Nómina y nómina electrónica DIAN
require __DIR__ . '/nomina.php';
require __DIR__ . '/obituarios.php';
require __DIR__ . '/contabilidad.php';
