<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// La donación de Navidad queda opcional: la casilla sale sin marcar y el negocio la marca si quiere ayudar.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ajustes')->updateOrInsert(['clave' => 'donacion_marcada'], ['valor' => '0', 'updated_at' => now()]);
    }

    public function down(): void
    {
    }
};
