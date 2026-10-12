<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// El curso de asesores es obligatorio para todos: los aspirantes que esperaban aprobación pasan a hacer el curso
// (al aprobarlo quedan activos solos). Los que ya estaban activos lo hacen al volver a entrar a su panel.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('asesores')->where('estado', 'pendiente')->update(['estado' => 'capacitacion', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('asesores')->where('estado', 'capacitacion')->update(['estado' => 'pendiente']);
    }
};
