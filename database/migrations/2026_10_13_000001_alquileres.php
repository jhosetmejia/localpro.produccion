<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Alquileres: el negocio pone en alquiler un servicio (sonido, salón, carpa, vestido, equipo…) con fecha, hora, lugar, responsable y valor
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alquileres', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('numero', 20);
            $t->string('que_alquila', 120);
            $t->date('fecha');
            $t->time('hora');
            $t->string('lugar', 150);
            $t->string('responsable_telefono', 20);
            $t->string('nota', 255)->nullable();
            $t->decimal('valor', 14, 2);
            $t->string('estado', 12)->default('programado'); // programado, realizado, cancelado
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tienda_id', 'numero']);
            $t->index(['tienda_id', 'estado', 'fecha']);
        });

        // Permiso para manejar alquileres: lo tienen quienes venden
        if (Schema::hasTable('permissions')) {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'usar-alquileres', 'guard_name' => 'web']);
            foreach (\Spatie\Permission\Models\Role::all() as $rol) {
                if ($rol->hasPermissionTo('usar-pos')) $rol->givePermissionTo($perm);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alquileres');
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'usar-alquileres')->delete();
        }
    }
};
