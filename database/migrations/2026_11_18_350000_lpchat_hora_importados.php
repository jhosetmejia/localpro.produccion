<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-18 (Jhoset): los mensajes traídos de NES/PFN se guardaron con la hora UTC y salían 5 horas adelantados
 * (ej. "1:13 am" de un mensaje de las 8:13 pm de ayer). Se pasan a la hora del sistema (America/Lima = UTC-5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lpchat_mensajes', 'origen') || DB::getDriverName() !== 'mysql') {
            return;
        }
        $h = (int) round(-now(config('app.timezone'))->utcOffset() / 60); // 5
        if ($h <= 0) {
            return;
        }
        DB::statement("UPDATE lpchat_mensajes SET created_at = DATE_SUB(created_at, INTERVAL {$h} HOUR), updated_at = DATE_SUB(updated_at, INTERVAL {$h} HOUR)
            WHERE origen IS NOT NULL AND (origen LIKE 'nes:m%' OR origen LIKE 'pfn:m%')");
    }

    public function down(): void
    {
    }
};
