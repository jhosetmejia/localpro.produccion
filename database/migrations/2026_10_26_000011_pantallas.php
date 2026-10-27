<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pantallas del negocio (cartelera digital): cada pantalla es un televisor que muestra, uno tras otro,
// las imágenes y videos que el negocio sube desde LocalProo. Sin cables ni USB: se abre con un código en el TV.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tiendas') && !Schema::hasColumn('tiendas', 'mod_pantallas')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->boolean('mod_pantallas')->default(false));
        }
        if (!Schema::hasTable('pantallas')) {
            Schema::create('pantallas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->string('nombre', 60);
                $t->string('token', 40)->unique();
                $t->string('codigo', 8)->nullable()->unique();
                $t->boolean('activa')->default(true);
                $t->string('ajuste', 10)->default('completa'); // completa (se ve todo) · llenar (llena la pantalla, puede recortar)
                $t->unsignedInteger('orden')->default(0);
                $t->timestamps();
                $t->index(['tienda_id', 'orden']);
            });
        }
        if (!Schema::hasTable('pantalla_items')) {
            Schema::create('pantalla_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->foreignId('pantalla_id')->constrained('pantallas')->cascadeOnDelete();
                $t->string('tipo', 10); // imagen · video
                $t->string('path');
                $t->string('nombre', 120)->nullable();
                $t->unsignedSmallInteger('segundos')->default(10); // solo imágenes; el video dura lo que dure
                $t->boolean('activo')->default(true);
                $t->unsignedInteger('orden')->default(0);
                $t->timestamps();
                $t->index(['pantalla_id', 'orden']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pantalla_items');
        Schema::dropIfExists('pantallas');
        if (Schema::hasColumn('tiendas', 'mod_pantallas')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('mod_pantallas'));
    }
};
