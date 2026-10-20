<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Comparta LocalProo y gane": un negocio invita a otro con su código (LCT-00001). Cada vez que el invitado paga
// su plan anual, el que lo invitó recibe un abono (de por vida) que se descuenta solo cuando renueva su plan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'referida_por_tienda_id')) $t->foreignId('referida_por_tienda_id')->nullable()->index();
            if (!Schema::hasColumn('tiendas', 'saldo_referidos')) $t->decimal('saldo_referidos', 14, 2)->default(0);
        });
        Schema::table('pagos_suscripcion', function (Blueprint $t) {
            if (!Schema::hasColumn('pagos_suscripcion', 'abono_saldo')) $t->decimal('abono_saldo', 14, 2)->nullable();
        });
        if (!Schema::hasTable('referido_movimientos')) {
            Schema::create('referido_movimientos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->index();            // negocio dueño del saldo
                $t->foreignId('referida_id')->nullable();         // negocio invitado que pagó
                $t->foreignId('pago_suscripcion_id')->nullable();
                $t->string('concepto', 20);                      // abono | uso | ajuste
                $t->decimal('valor', 14, 2);                     // + abono, - uso
                $t->string('moneda', 3)->default('COP');
                $t->string('nota', 255)->nullable();
                $t->timestamps();
                $t->unique(['pago_suscripcion_id', 'concepto']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referido_movimientos');
        Schema::table('pagos_suscripcion', fn (Blueprint $t) => $t->dropColumn('abono_saldo'));
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['referida_por_tienda_id', 'saldo_referidos']));
    }
};
