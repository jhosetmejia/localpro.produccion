<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Contactar clientes nuevos": a cada persona del equipo se le reparten contactos al día para escribirles
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $t) {
            if (!Schema::hasColumn('clientes', 'asignado_a')) $t->foreignId('asignado_a')->nullable()->index();
            if (!Schema::hasColumn('clientes', 'asignado_fecha')) $t->date('asignado_fecha')->nullable()->index();
            if (!Schema::hasColumn('clientes', 'contactado_at')) $t->timestamp('contactado_at')->nullable()->index();
            if (!Schema::hasColumn('clientes', 'contactado_por')) $t->foreignId('contactado_por')->nullable();
            if (!Schema::hasColumn('clientes', 'contacto_estado')) $t->string('contacto_estado', 20)->nullable();
        });
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'mensaje_contacto')) $t->text('mensaje_contacto')->nullable();
            if (!Schema::hasColumn('tiendas', 'contactos_diarios')) $t->unsignedSmallInteger('contactos_diarios')->default(20);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', fn (Blueprint $t) => $t->dropColumn(['asignado_a', 'asignado_fecha', 'contactado_at', 'contactado_por', 'contacto_estado']));
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['mensaje_contacto', 'contactos_diarios']));
    }
};
