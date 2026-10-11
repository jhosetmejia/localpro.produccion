<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// La renovación anual le deja al asesor $5.000 de por vida (mientras el negocio y el asesor estén activos). Se puede cambiar en Valores de comisión.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ajustes')->updateOrInsert(['clave' => 'red_renov_venta'], ['valor' => '5000', 'updated_at' => now(), 'created_at' => now()]);
    }

    public function down(): void
    {
    }
};
