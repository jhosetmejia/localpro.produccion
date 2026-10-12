<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Facturación electrónica DIAN (Colombia): datos fiscales del emisor y del cliente, IVA por producto,
// impuestos discriminados en cada venta y registro de los documentos electrónicos enviados por el proveedor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            $table->char('dv', 1)->nullable()->after('nit');
            $table->string('razon_social', 120)->nullable()->after('nombre');
            $table->string('tipo_persona', 10)->nullable()->after('dv'); // natural | juridica
            $table->string('responsabilidades', 80)->nullable()->after('regimen'); // códigos DIAN separados por coma (R-99-PN, O-13, O-15, O-23, O-47)
            $table->string('ciiu', 10)->nullable();
            $table->string('municipio_dane', 5)->nullable();
            $table->string('email_facturacion', 120)->nullable();
            // Documento equivalente electrónico POS (usa la resolución POS que ya existía)
            $table->boolean('pos_electronico')->default(false);
            // Factura electrónica de venta: resolución propia
            $table->boolean('fe_activa')->default(false);
            $table->string('fe_ambiente', 12)->default('pruebas'); // pruebas | produccion
            $table->string('fe_resolucion_numero', 40)->nullable();
            $table->date('fe_resolucion_fecha')->nullable();
            $table->date('fe_resolucion_vigencia')->nullable();
            $table->string('fe_prefijo', 10)->nullable();
            $table->unsignedBigInteger('fe_desde')->nullable();
            $table->unsignedBigInteger('fe_hasta')->nullable();
            $table->text('fe_clave_tecnica')->nullable(); // cifrada
            $table->unsignedBigInteger('consecutivo_fe')->default(0);
            $table->text('fe_credenciales')->nullable(); // cifradas: usuario/token del proveedor para esta tienda
        });

        Schema::table('productos', function (Blueprint $table) {
            // ninguno | iva19 | iva5 | exento | excluido | inc8
            $table->string('impuesto', 10)->default('ninguno')->after('precio_venta');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->char('dv', 1)->nullable()->after('numero_documento');
            $table->string('responsabilidad', 20)->nullable()->after('dv'); // R-99-PN por defecto
            $table->string('municipio_dane', 5)->nullable()->after('direccion');
        });

        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->string('medio_dian', 4)->nullable()->after('nombre'); // 10 efectivo, 47 transferencia, 48 crédito, 49 débito, ZZZ otro
        });

        Schema::table('pos_ventas', function (Blueprint $table) {
            $table->string('tipo_doc', 10)->default('tiquete')->after('numero'); // tiquete | pos_e | fe
            $table->decimal('base_gravable', 14, 2)->default(0)->after('descuento');
            $table->decimal('impuestos', 14, 2)->default(0)->after('base_gravable');
            $table->json('impuestos_detalle')->nullable()->after('impuestos');
            $table->json('adquiriente')->nullable()->after('cliente_telefono'); // foto de los datos del cliente al facturar
        });

        Schema::table('pos_venta_items', function (Blueprint $table) {
            $table->string('impuesto', 10)->default('ninguno')->after('precio');
            $table->decimal('iva_tasa', 5, 2)->default(0)->after('impuesto');
            $table->decimal('base', 14, 2)->default(0)->after('iva_tasa');
            $table->decimal('iva_valor', 14, 2)->default(0)->after('base');
        });

        Schema::create('documentos_electronicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->string('tipo', 6); // fe | pos_e | nc
            $table->foreignId('pos_venta_id')->nullable()->constrained('pos_ventas')->nullOnDelete();
            $table->foreignId('devolucion_id')->nullable()->constrained('devoluciones')->nullOnDelete();
            $table->foreignId('referencia_id')->nullable()->constrained('documentos_electronicos')->nullOnDelete(); // la factura que corrige una nota crédito
            $table->string('numero', 30);
            $table->string('prefijo', 10)->nullable();
            $table->unsignedBigInteger('consecutivo')->nullable();
            $table->string('cufe', 120)->nullable();
            $table->string('estado', 12)->default('pendiente'); // pendiente | enviado | aceptado | rechazado | error | simulado
            $table->string('ambiente', 12)->default('pruebas');
            $table->string('proveedor', 30)->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->text('ultimo_error')->nullable();
            $table->text('qr')->nullable();
            $table->text('pdf_url')->nullable();
            $table->text('xml_url')->nullable();
            $table->json('payload')->nullable();
            $table->json('respuesta')->nullable();
            $table->dateTime('enviado_en')->nullable();
            $table->dateTime('validado_en')->nullable();
            $table->timestamps();
            $table->unique(['tienda_id', 'tipo', 'numero']);
            $table->index(['estado', 'intentos']);
        });

        // Medios de pago DIAN para los métodos que ya existen
        foreach (DB::table('metodos_pago')->get(['id', 'nombre', 'es_efectivo']) as $m) {
            $n = mb_strtolower($m->nombre);
            $cod = $m->es_efectivo ? '10'
                : (str_contains($n, 'débito') || str_contains($n, 'debito') ? '49'
                : (str_contains($n, 'tarjeta') || str_contains($n, 'crédito') || str_contains($n, 'credito') ? '48'
                : (str_contains($n, 'nequi') || str_contains($n, 'daviplata') || str_contains($n, 'transfer') || str_contains($n, 'bancolombia') ? '47' : 'ZZZ')));
            DB::table('metodos_pago')->where('id', $m->id)->update(['medio_dian' => $cod]);
        }

        // Datos fiscales a partir de lo que ya tenía cada tienda
        DB::table('tiendas')->where('regimen', 'Responsable de IVA')->update(['responsabilidades' => 'O-48']);
        DB::table('tiendas')->where('regimen', 'Régimen simple de tributación')->update(['responsabilidades' => 'O-47']);
        DB::table('tiendas')->where('regimen', 'No responsable de IVA')->update(['responsabilidades' => 'R-99-PN']);

        if (Schema::hasTable('permissions')) {
            $p = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'facturacion-dian', 'guard_name' => 'web']);
            foreach (\Spatie\Permission\Models\Role::all() as $rol) {
                if ($rol->hasPermissionTo('editar-negocio')) $rol->givePermissionTo($p);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_electronicos');
        Schema::table('pos_venta_items', fn (Blueprint $t) => $t->dropColumn(['impuesto', 'iva_tasa', 'base', 'iva_valor']));
        Schema::table('pos_ventas', fn (Blueprint $t) => $t->dropColumn(['tipo_doc', 'base_gravable', 'impuestos', 'impuestos_detalle', 'adquiriente']));
        Schema::table('metodos_pago', fn (Blueprint $t) => $t->dropColumn('medio_dian'));
        Schema::table('personas', fn (Blueprint $t) => $t->dropColumn(['dv', 'responsabilidad', 'municipio_dane']));
        Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('impuesto'));
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn([
            'dv', 'razon_social', 'tipo_persona', 'responsabilidades', 'ciiu', 'municipio_dane', 'email_facturacion', 'pos_electronico',
            'fe_activa', 'fe_ambiente', 'fe_resolucion_numero', 'fe_resolucion_fecha', 'fe_resolucion_vigencia', 'fe_prefijo', 'fe_desde', 'fe_hasta',
            'fe_clave_tecnica', 'consecutivo_fe', 'fe_credenciales',
        ]));
    }
};
