<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Prospectos comerciales (firmas contables, etc.): de dónde salió su correo, en qué paso de la secuencia van
// y si pidieron no recibir más correos. Cada envío queda registrado en prospecto_envios.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('prospectos')) {
            Schema::create('prospectos', function (Blueprint $t) {
                $t->id();
                $t->string('empresa', 150);
                $t->string('nombre', 120)->nullable();       // persona de contacto, si se conoce
                $t->string('correo', 150)->nullable()->unique();
                $t->string('telefono', 40)->nullable();
                $t->string('ciudad', 80)->nullable();
                $t->string('web', 255)->nullable()->unique();
                $t->string('fuente', 255)->nullable();       // página donde se publicó el correo (constancia)
                $t->string('segmento', 30)->default('contador');
                // sin_correo: falta buscar el correo · nuevo: listo para el 1.er correo · en_secuencia · terminado
                // respondio · registrado · baja · rebotado · descartado
                $t->string('estado', 20)->default('sin_correo')->index();
                $t->unsignedTinyInteger('paso')->default(0);  // correos de la secuencia ya enviados (0 a 3)
                $t->timestamp('ultimo_envio')->nullable();
                $t->date('proximo_envio')->nullable()->index();
                $t->unsignedTinyInteger('intentos_busqueda')->default(0);
                $t->string('token', 40)->unique();            // enlace de baja
                $t->timestamp('baja_at')->nullable();
                $t->string('notas', 500)->nullable();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('prospecto_envios')) {
            Schema::create('prospecto_envios', function (Blueprint $t) {
                $t->id();
                $t->foreignId('prospecto_id')->constrained('prospectos')->cascadeOnDelete();
                $t->unsignedTinyInteger('paso');
                $t->string('correo', 150);
                $t->string('asunto', 200);
                $t->string('estado', 15);                    // enviado · fallido · simulado
                $t->string('detalle', 500)->nullable();
                $t->timestamps();
                $t->index(['created_at', 'estado']);
            });
        }
        if (!Schema::hasTable('prospecto_busquedas')) {
            Schema::create('prospecto_busquedas', function (Blueprint $t) {
                $t->id();
                $t->string('consulta', 150)->unique();       // ej. "firma contable en Pasto"
                $t->unsignedSmallInteger('encontrados')->default(0);
                $t->timestamp('hecha_at')->nullable();
                $t->timestamps();
            });
        }

        // Primera tanda: firmas contables de Pasto e Ipiales con página web (el sistema busca su correo de contacto)
        $semilla = [
            ['CGO Soluciones Contables', 'Pasto', 'https://cgosolucionescontables-alt.github.io/cgosolucionescontables/', '+57 304 4460454'],
            ['Firma de Contadores Summa', 'Pasto', 'https://contadoressumma.com/', '+57 317 6755410'],
            ['Germán Montenegro Fajardo Auditores y Asesores SAS', 'Pasto', 'https://www.gmf.com.co/', '+57 318 4183159'],
            ['PMC Asesoría Empresarial SAS', 'Pasto', 'https://www.pmcasesoria.com/', '+57 316 4910070'],
            ['Asesorías Contables y Tributarias Montilla', 'Pasto', 'http://www.asesoriascontablesmontilla.com/', '+57 602 7292007'],
        ];
        foreach ($semilla as [$empresa, $ciudad, $web, $tel]) {
            DB::table('prospectos')->insertOrIgnore([
                'empresa' => $empresa, 'ciudad' => $ciudad, 'web' => $web, 'telefono' => $tel,
                'estado' => 'sin_correo', 'token' => Str::random(32), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prospecto_busquedas');
        Schema::dropIfExists('prospecto_envios');
        Schema::dropIfExists('prospectos');
    }
};
