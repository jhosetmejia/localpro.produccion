<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Planes de LocalPro con vencimiento y pagos (manual o Wompi), y resolución DIAN para el tiquete POS.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 40);
            $table->decimal('precio', 14, 2)->default(0); // en pesos colombianos
            $table->unsignedSmallInteger('dias')->nullable(); // vacío = no vence
            $table->unsignedSmallInteger('max_usuarios')->nullable();
            $table->unsignedInteger('max_productos')->nullable();
            $table->string('descripcion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
        $ahora = now();
        DB::table('planes')->insert([
            ['nombre' => 'Gratis', 'precio' => 0, 'dias' => null, 'max_usuarios' => null, 'max_productos' => null, 'descripcion' => 'Para empezar a vender hoy mismo.', 'activo' => true, 'orden' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Mensual', 'precio' => 49900, 'dias' => 30, 'max_usuarios' => null, 'max_productos' => null, 'descripcion' => 'Todo LocalPro, pago mes a mes.', 'activo' => true, 'orden' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Anual', 'precio' => 499000, 'dias' => 365, 'max_usuarios' => null, 'max_productos' => null, 'descripcion' => 'Todo LocalPro por un año (2 meses gratis).', 'activo' => true, 'orden' => 3, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        Schema::table('tiendas', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('estado')->constrained('planes')->nullOnDelete();
            $table->date('plan_vence')->nullable()->after('plan_id');
            // Facturación DIAN (documento equivalente POS)
            $table->string('regimen', 40)->nullable()->after('nit');
            $table->string('resolucion_numero', 40)->nullable();
            $table->date('resolucion_fecha')->nullable();
            $table->date('resolucion_vigencia')->nullable();
            $table->string('resolucion_prefijo', 10)->nullable();
            $table->unsignedInteger('resolucion_desde')->nullable();
            $table->unsignedInteger('resolucion_hasta')->nullable();
        });
        $gratis = DB::table('planes')->where('nombre', 'Gratis')->value('id');
        DB::table('tiendas')->update(['plan_id' => $gratis]);

        Schema::create('pagos_suscripcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('planes')->nullOnDelete();
            $table->decimal('monto', 14, 2);
            $table->string('metodo', 20); // manual | wompi
            $table->string('referencia', 80)->unique();
            $table->string('estado', 15)->default('pendiente'); // pendiente | aprobado | rechazado
            $table->date('periodo_desde')->nullable();
            $table->date('periodo_hasta')->nullable();
            $table->string('nota', 255)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->json('respuesta')->nullable();
            $table->timestamps();
        });

        Schema::create('ajustes', function (Blueprint $table) {
            $table->string('clave', 60)->primary();
            $table->text('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('pagos_suscripcion');
        Schema::table('tiendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['plan_vence', 'regimen', 'resolucion_numero', 'resolucion_fecha', 'resolucion_vigencia', 'resolucion_prefijo', 'resolucion_desde', 'resolucion_hasta']);
        });
        Schema::dropIfExists('planes');
    }
};
