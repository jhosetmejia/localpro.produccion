<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Depuración de negocios que no pasaron a un plan pago al terminar la prueba, y aceptación de condiciones al registrarse.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'datos_depurados_at')) {
            Schema::table('tiendas', function (Blueprint $table) {
                $table->timestamp('datos_depurados_at')->nullable(); // cuándo se borraron productos, ventas, imágenes…
            });
        }
        // Registro de cada aviso de vencimiento (correo enviado o pantalla vista): prueba de que se le notificó
        if (!Schema::hasTable('avisos_plan')) {
            Schema::create('avisos_plan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $table->string('tipo', 30);          // por_vencer | vencida | aviso_final | datos_eliminados
                $table->string('canal', 10);         // correo | sistema
                $table->string('estado', 12);        // enviado | fallido | visto
                $table->date('plan_vence')->nullable(); // periodo al que corresponde el aviso
                $table->date('fecha_borrado')->nullable(); // fecha de eliminación anunciada en el aviso
                $table->string('destinatario', 160)->nullable();
                $table->string('asunto', 200)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // quién vio la pantalla
                $table->string('ip', 45)->nullable();
                $table->text('detalle')->nullable();
                $table->timestamps();
                $table->index(['tienda_id', 'tipo', 'plan_vence']);
            });
        }
        if (!Schema::hasColumn('users', 'terminos_aceptados_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('terminos_aceptados_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_plan');
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('datos_depurados_at'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('terminos_aceptados_at'));
    }
};
