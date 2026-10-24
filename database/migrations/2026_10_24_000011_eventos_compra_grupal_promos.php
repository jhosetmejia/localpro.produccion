<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Eventos: comprar varias entradas en una sola compra (cada persona con su nombre, documento y su propio QR)
// y promociones que arma el organizador ("Compra 2 y la 3.ª es gratis", "Compra 5 y la 6.ª con 50 % de descuento").
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $t) {
            if (!Schema::hasColumn('eventos', 'promos')) $t->json('promos')->nullable();
            if (!Schema::hasColumn('eventos', 'max_por_compra')) $t->unsignedSmallInteger('max_por_compra')->default(10);
        });
        Schema::table('evento_entradas', function (Blueprint $t) {
            if (!Schema::hasColumn('evento_entradas', 'grupo')) $t->uuid('grupo')->nullable()->index();
            if (!Schema::hasColumn('evento_entradas', 'titular_nombre')) $t->string('titular_nombre', 120)->nullable();
            if (!Schema::hasColumn('evento_entradas', 'titular_tipo_documento')) $t->string('titular_tipo_documento', 4)->nullable();
            if (!Schema::hasColumn('evento_entradas', 'titular_documento')) $t->string('titular_documento', 30)->nullable()->index();
            if (!Schema::hasColumn('evento_entradas', 'titular_email')) $t->string('titular_email', 150)->nullable();
            if (!Schema::hasColumn('evento_entradas', 'precio_lista')) $t->decimal('precio_lista', 14, 2)->nullable();
            if (!Schema::hasColumn('evento_entradas', 'promo')) $t->string('promo', 120)->nullable();
        });
        // Un comprador ya puede tener varias entradas del mismo evento (las de sus acompañantes)
        try {
            Schema::table('evento_entradas', fn (Blueprint $t) => $t->dropUnique(['evento_id', 'asistente_id']));
        } catch (\Throwable $e) {
            // ya no existía
        }
        try {
            Schema::table('evento_entradas', fn (Blueprint $t) => $t->index(['evento_id', 'asistente_id']));
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::table('eventos', fn (Blueprint $t) => $t->dropColumn(['promos', 'max_por_compra']));
        Schema::table('evento_entradas', fn (Blueprint $t) => $t->dropColumn(['grupo', 'titular_nombre', 'titular_tipo_documento', 'titular_documento', 'titular_email', 'precio_lista', 'promo']));
    }
};
