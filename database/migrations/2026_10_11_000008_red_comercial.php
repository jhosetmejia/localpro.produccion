<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Red comercial: asesores, líderes de ciudad y de departamento, los negocios que trae cada uno y sus comisiones.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asesores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('cargo', 20)->default('asesor'); // asesor | lider_ciudad | lider_departamento
            $table->foreignId('padre_id')->nullable()->constrained('asesores')->nullOnDelete(); // su líder directo
            $table->string('pais', 2)->default('CO');
            $table->string('departamento', 60)->nullable();
            $table->string('ciudad', 80)->nullable();
            $table->string('codigo', 20)->unique(); // código / enlace para inscribir negocios
            $table->string('documento', 20)->nullable();
            $table->string('banco', 120)->nullable(); // dónde se le paga la comisión (Nequi, cuenta…)
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['cargo', 'departamento', 'ciudad']);
        });

        Schema::table('tiendas', function (Blueprint $table) {
            $table->foreignId('asesor_id')->nullable()->after('plan_vence')->constrained('asesores')->nullOnDelete();
        });

        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesor_id')->constrained('asesores')->cascadeOnDelete();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->foreignId('pago_suscripcion_id')->nullable()->constrained('pagos_suscripcion')->nullOnDelete();
            $table->string('concepto', 20); // venta | lider_ciudad | lider_departamento
            $table->string('tipo', 12);     // nueva | renovacion
            $table->decimal('monto', 12, 2);
            $table->string('estado', 12)->default('pendiente'); // pendiente | pagada | anulada
            $table->timestamp('pagada_at')->nullable();
            $table->string('nota', 255)->nullable();
            $table->timestamps();
            $table->unique(['pago_suscripcion_id', 'concepto']);
            $table->index(['asesor_id', 'estado']);
        });

        // Valores por defecto (plan anual de 200.000 vendido por asesor)
        $ahora = now();
        foreach ([
            'red_valor_plan' => 200000,
            'red_nueva_venta' => 80000, 'red_nueva_ciudad' => 10000, 'red_nueva_departamento' => 10000,
            'red_renov_venta' => 10000, 'red_renov_ciudad' => 10000, 'red_renov_departamento' => 10000,
        ] as $clave => $valor) {
            if (!DB::table('ajustes')->where('clave', $clave)->exists()) {
                DB::table('ajustes')->insert(['clave' => $clave, 'valor' => $valor, 'created_at' => $ahora, 'updated_at' => $ahora]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones');
        Schema::table('tiendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asesor_id');
        });
        Schema::dropIfExists('asesores');
    }
};
