<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Los mensajes que llegaron de Facebook/Instagram se guardaron con la hora UTC (5 horas adelante).
// Se corrigen los que trajo Meta (los enviados desde LocalPro ya tenían la hora bien) y se recalcula cada chat.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('redes_mensajes')) return;
        $min = (int) now(config('app.timezone'))->utcOffset(); // Lima: -300
        if ($min !== 0) {
            DB::table('redes_mensajes')->whereNull('user_id')->whereNotNull('enviado_en')
                ->update(['enviado_en' => DB::raw('DATE_ADD(enviado_en, INTERVAL ' . $min . ' MINUTE)')]);
        }
        foreach (DB::table('redes_conversaciones')->pluck('id') as $id) {
            $ultimo = DB::table('redes_mensajes')->where('conversacion_id', $id)->orderByDesc('enviado_en')->orderByDesc('id')->first();
            if (!$ultimo) continue;
            DB::table('redes_conversaciones')->where('id', $id)->update([
                'ultimo_en' => $ultimo->enviado_en,
                'ultimo_texto' => mb_substr($ultimo->texto ?: ($ultimo->adjunto_url ? '📷 Imagen' : ''), 0, 250),
                'ultimo_del_cliente_en' => DB::table('redes_mensajes')->where('conversacion_id', $id)->where('del_cliente', true)->max('enviado_en'),
            ]);
        }
    }

    public function down(): void
    {
    }
};
