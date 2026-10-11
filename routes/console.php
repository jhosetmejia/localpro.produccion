<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Crea o actualiza el superadministrador de LocalPro
// Uso: php artisan localpro:superadmin correo@ejemplo.com 'clave' --nombre="Jhoset Mejía"
Artisan::command('localpro:superadmin {email} {clave} {--nombre=Administrador LocalPro}', function () {
    $email = strtolower(trim($this->argument('email')));
    $clave = (string) $this->argument('clave');
    if (strlen($clave) < 8) {
        $this->error('La clave debe tener mínimo 8 caracteres.');
        return 1;
    }
    $u = \App\Models\User::where('email', $email)->first();
    if ($u) {
        $u->forceFill(['password' => \Illuminate\Support\Facades\Hash::make($clave), 'es_superadmin' => true])->save();
        $this->info("Listo: $email ya existía y ahora es superadministrador (se actualizó la clave)." . ($u->tienda_id ? ' Conserva su tienda.' : ''));
    } else {
        \App\Models\User::create([
            'name' => $this->option('nombre'),
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make($clave),
        ])->forceFill(['es_superadmin' => true, 'tienda_id' => null])->save();
        $this->info("Listo: superadministrador $email creado.");
    }
    return 0;
})->purpose('Crear o actualizar el superadministrador');
