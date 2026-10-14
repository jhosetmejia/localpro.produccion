<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Alquiler:
//  - Módulo por negocio (mod_alquiler): solo lo ve quien maneja alquileres (una droguería no lo necesita)
//  - Abonos: el cliente puede pagar todo o ir abonando; al completar se factura
//  - Entrega y devolución: saber si lo alquilado está en manos del cliente o ya lo devolvió (en orden o con novedad)
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'mod_alquiler')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->boolean('mod_alquiler')->default(false));
            // Los negocios que ya registraron alquileres lo siguen viendo
            if (Schema::hasTable('alquileres')) {
                DB::table('tiendas')->whereIn('id', DB::table('alquileres')->select('tienda_id'))->update(['mod_alquiler' => true]);
            }
        }

        Schema::table('alquileres', function (Blueprint $t) {
            $t->decimal('abonado', 14, 2)->default(0)->after('comision');
            $t->string('entrega', 12)->default('pendiente')->after('estado'); // pendiente, alquilado, devuelto
            $t->dateTime('entregado_en')->nullable()->after('entrega');
            $t->dateTime('devuelto_en')->nullable()->after('entregado_en');
            $t->string('devolucion_estado', 12)->nullable()->after('devuelto_en'); // en_orden, con_novedad
            $t->string('devolucion_nota', 255)->nullable()->after('devolucion_estado');
        });
        // Los que ya se cobraron completos quedan con todo abonado
        DB::table('alquileres')->whereNotNull('pos_venta_id')->update(['abonado' => DB::raw('valor')]);

        Schema::create('alquiler_abonos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('alquiler_id')->constrained('alquileres')->cascadeOnDelete();
            $t->string('numero', 20);
            $t->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $t->decimal('monto', 14, 2);
            $t->decimal('saldo_despues', 14, 2);
            $t->string('nota', 255)->nullable();
            $t->boolean('anulado')->default(false);
            $t->timestamps();
            $t->unique(['tienda_id', 'numero']);
            $t->index(['caja_id', 'anulado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alquiler_abonos');
        Schema::table('alquileres', fn (Blueprint $t) => $t->dropColumn(['abonado', 'entrega', 'entregado_en', 'devuelto_en', 'devolucion_estado', 'devolucion_nota']));
        if (Schema::hasColumn('tiendas', 'mod_alquiler')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('mod_alquiler'));
    }
};
