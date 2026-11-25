<?php

use App\Support\LpChat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 2026-11-25 (Jhoset): la felicitación ya enviada decía "fuiste elegid@ como MODELO IMAGEN": se edita el mensaje para que diga
// CANDIDAT@ a Modelo Imagen (si no, van a pensar que ya ganaron el concurso).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_mensajes')) {
            return;
        }
        $cambios = [
            'fuiste elegid@ como MODELO IMAGEN' => 'quedaste entre los CANDIDATOS a MODELO IMAGEN',
            'fue elegid@ como MODELO IMAGEN' => 'quedó entre los CANDIDATOS a MODELO IMAGEN',
            'Estás entre los candidatos top y es un verdadero honor' => 'Estás en el top y es un verdadero honor',
            'Ahora vas a ser la imagen de nuestro evento y queremos que este camino te abra muchas puertas.' => 'Ahora vamos por el título: pídele a tu familia, amigos y seguidores que voten por ti en narinoexposhow.com/moda. ¡Cada voto cuenta!',
        ];
        DB::table('lpchat_mensajes')->where('created_at', '>=', '2026-11-25 00:00:00')->whereNotNull('texto')
            ->orderBy('id')->select('id', 'texto')->chunkById(500, function ($filas) use ($cambios) {
                foreach ($filas as $f) {
                    $t = (string) LpChat::leer($f->texto);
                    if (! str_contains($t, 'elegid@ como MODELO IMAGEN')) {
                        continue;
                    }
                    DB::table('lpchat_mensajes')->where('id', $f->id)->update(['texto' => LpChat::cifrar(strtr($t, $cambios))]);
                }
            });
    }

    public function down(): void
    {
    }
};
