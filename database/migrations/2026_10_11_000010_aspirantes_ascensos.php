<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Aspirantes (los aprueba quien los invitó), quién reclutó a quién, historial de la red,
// metas para subir de rango y plan de prueba gratis de 3 meses para negocios traídos por asesores.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesores', function (Blueprint $table) {
            if (!Schema::hasColumn('asesores', 'estado')) {
                $table->string('estado', 12)->default('activo')->after('activo'); // pendiente | activo | rechazado
                $table->foreignId('reclutado_por')->nullable()->after('padre_id')->constrained('asesores')->nullOnDelete();
                $table->timestamp('aprobado_at')->nullable()->after('estado');
            }
        });

        if (!Schema::hasTable('asesor_historial')) {
            Schema::create('asesor_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asesor_id')->constrained('asesores')->cascadeOnDelete();
                $table->string('accion', 30); // inscrito | solicitud | aprobado | rechazado | ascenso | cambio_lider | inactivo
                $table->string('detalle', 255)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // quién lo hizo
                $table->timestamps();
            });
        }

        $ahora = now();
        foreach (['red_meta_negocios' => 10, 'red_meta_asesores' => 10] as $k => $v) {
            if (!DB::table('ajustes')->where('clave', $k)->exists()) {
                DB::table('ajustes')->insert(['clave' => $k, 'valor' => $v, 'created_at' => $ahora, 'updated_at' => $ahora]);
            }
        }

        if (!DB::table('planes')->where('nombre', 'Prueba 3 meses')->exists()) {
            DB::table('planes')->insert([
                'nombre' => 'Prueba 3 meses', 'precio' => 0, 'dias' => 90, 'max_usuarios' => null, 'max_productos' => null,
                'descripcion' => 'Prueba gratis por 3 meses (negocios inscritos por un asesor).',
                'activo' => false, 'orden' => 9, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asesor_historial');
        Schema::table('asesores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reclutado_por');
            $table->dropColumn(['estado', 'aprobado_at']);
        });
    }
};
