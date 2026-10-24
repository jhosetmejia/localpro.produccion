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

// Roles por defecto (Caja, Mesero, Cocina…) para los negocios con Pedidos que todavía no tienen roles
Artisan::command('pedidos:roles-por-defecto {--simular}', function () {
    $n = 0;
    \App\Models\Tienda::where('mod_pedidos', true)->orderBy('id')->each(function ($t) use (&$n) {
        $tiene = \App\Support\TiendaActual::como($t->id, fn () => \App\Models\NegocioRol::exists());
        if ($tiene) return;
        $roles = implode(', ', \App\Support\RolesNegocio::plantillasPorDefecto($t->tipo_negocio, true));
        if ($this->option('simular')) {
            $this->line("[simular] {$t->nombre} ({$t->tipo_negocio}): {$roles}");
        } else {
            $c = \App\Support\RolesNegocio::crearPorDefecto($t);
            $this->line("{$t->nombre}: {$c} roles ({$roles})");
        }
        $n++;
    });
    $this->info("Negocios: {$n}");
})->purpose('Crea los roles por defecto en negocios con Pedidos que no tienen roles');

// Roles por defecto según el tipo de negocio para los negocios que todavía no tienen ningún rol
Artisan::command('negocios:roles-por-defecto {--simular}', function () {
    $n = 0;
    \App\Models\Tienda::whereNotNull('tipo_negocio')->orderBy('id')->each(function ($t) use (&$n) {
        if (\App\Support\TiendaActual::como($t->id, fn () => \App\Models\NegocioRol::exists())) return;
        $roles = \App\Support\RolesNegocio::plantillasPorDefecto($t->tipo_negocio, (bool) $t->mod_pedidos);
        if (!$roles) return;
        if ($this->option('simular')) {
            $this->line("[simular] {$t->nombre} ({$t->tipo_negocio}): " . implode(', ', $roles));
        } else {
            $this->line("{$t->nombre}: " . \App\Support\RolesNegocio::crearPorDefecto($t) . ' roles');
        }
        $n++;
    });
    $this->info("Negocios: {$n}");
})->purpose('Crea los roles por defecto (según el tipo) en negocios que no tienen roles');
