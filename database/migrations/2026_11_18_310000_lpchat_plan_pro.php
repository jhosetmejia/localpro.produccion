<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-18 (Jhoset): Plan Pro de LP Chat ($5.000 al mes): en Conocer gente saluda hasta 100 personas nuevas al día (gratis: 5).
 * Pago por Nequi + comprobante; el súper administrador lo activa por 30 días. Quien ya es Pro en NES también lo es aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lpchat_usuarios') && ! Schema::hasColumn('lpchat_usuarios', 'pro_hasta')) {
            Schema::table('lpchat_usuarios', function (Blueprint $t) {
                $t->timestamp('pro_hasta')->nullable();
                $t->boolean('sin_limite')->default(false); // equipo de NES, marcas patrocinadoras
            });
        }
        if (! Schema::hasTable('lpchat_planes')) {
            Schema::create('lpchat_planes', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id')->index();
                $t->string('comprobante', 255)->nullable();
                $t->string('estado', 12)->default('pendiente'); // pendiente | activo | rechazado
                $t->timestamp('hasta')->nullable();
                $t->unsignedBigInteger('revisado_por')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lpchat_planes');
        if (Schema::hasColumn('lpchat_usuarios', 'pro_hasta')) {
            Schema::table('lpchat_usuarios', fn (Blueprint $t) => $t->dropColumn(['pro_hasta', 'sin_limite']));
        }
    }
};
