<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mis redes: publicar videos (Reels en Facebook e Instagram) y subirlos a YouTube (Short o video normal)
return new class extends Migration
{
    public function up(): void
    {
        // Una publicación puede ir solo a YouTube: la página de Facebook ya no es obligatoria
        Schema::table('redes_publicaciones', function (Blueprint $t) {
            $t->unsignedBigInteger('cuenta_id')->nullable()->change();
        });

        Schema::table('redes_publicaciones', function (Blueprint $t) {
            if (!Schema::hasColumn('redes_publicaciones', 'tipo')) $t->string('tipo', 10)->default('foto')->after('producto_id'); // foto o video
            if (!Schema::hasColumn('redes_publicaciones', 'video')) $t->string('video', 255)->nullable()->after('imagen');   // ruta en el disco público
            if (!Schema::hasColumn('redes_publicaciones', 'portada')) $t->string('portada', 255)->nullable()->after('video'); // JPG elegido del video
            if (!Schema::hasColumn('redes_publicaciones', 'duracion')) $t->decimal('duracion', 8, 2)->nullable()->after('portada');
            if (!Schema::hasColumn('redes_publicaciones', 'ancho')) $t->unsignedSmallInteger('ancho')->nullable()->after('duracion');
            if (!Schema::hasColumn('redes_publicaciones', 'alto')) $t->unsignedSmallInteger('alto')->nullable()->after('ancho');
            if (!Schema::hasColumn('redes_publicaciones', 'peso')) $t->unsignedBigInteger('peso')->nullable()->after('alto');
            if (!Schema::hasColumn('redes_publicaciones', 'en_youtube')) $t->boolean('en_youtube')->default(false)->after('en_instagram');
            if (!Schema::hasColumn('redes_publicaciones', 'yt_cuenta_id')) $t->foreignId('yt_cuenta_id')->nullable()->after('en_youtube')->constrained('redes_cuentas')->nullOnDelete();
            if (!Schema::hasColumn('redes_publicaciones', 'titulo')) $t->string('titulo', 120)->nullable()->after('yt_cuenta_id');
            if (!Schema::hasColumn('redes_publicaciones', 'yt_privacidad')) $t->string('yt_privacidad', 10)->default('public')->after('titulo');
            if (!Schema::hasColumn('redes_publicaciones', 'yt_video_id')) $t->string('yt_video_id', 40)->nullable()->after('ig_media_id');
            if (!Schema::hasColumn('redes_publicaciones', 'yt_url')) $t->string('yt_url', 200)->nullable()->after('ig_url');
            if (!Schema::hasColumn('redes_publicaciones', 'yt_subida')) $t->text('yt_subida')->nullable();         // dirección de la subida reanudable de YouTube
            if (!Schema::hasColumn('redes_publicaciones', 'ig_contenedor')) $t->string('ig_contenedor', 80)->nullable(); // Reel que Instagram está procesando
            if (!Schema::hasColumn('redes_publicaciones', 'fallos')) $t->json('fallos')->nullable();                 // red => motivo, para no reintentar sola la que falló
            if (!Schema::hasColumn('redes_publicaciones', 'empezo_en')) $t->timestamp('empezo_en')->nullable();       // cuándo arrancó a publicar (para cortar esperas eternas)
        });
    }

    public function down(): void
    {
        Schema::table('redes_publicaciones', function (Blueprint $t) {
            if (Schema::hasColumn('redes_publicaciones', 'yt_cuenta_id')) $t->dropConstrainedForeignId('yt_cuenta_id');
            $t->dropColumn(array_values(array_filter(['tipo', 'video', 'portada', 'duracion', 'ancho', 'alto', 'peso', 'en_youtube', 'titulo', 'yt_privacidad',
                'yt_video_id', 'yt_url', 'yt_subida', 'ig_contenedor', 'fallos', 'empezo_en'], fn ($c) => Schema::hasColumn('redes_publicaciones', $c))));
        });
    }
};
