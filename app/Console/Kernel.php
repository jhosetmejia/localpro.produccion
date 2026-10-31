<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('wompi:conciliar')->everyTenMinutes()->withoutOverlapping();
        // Vigilante de errores: si alguien recibe un error, le llega un correo al súper administrador
        $schedule->command('localpro:vigilar-errores')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('redes:publicar-programadas')->everyMinute()->withoutOverlapping();
        // Plan B de mensajes: revisa chats nuevos aunque los avisos en tiempo real de Meta no lleguen
        $schedule->command('redes:sincronizar --limite=10')->everyTwoMinutes()->withoutOverlapping(10)->runInBackground();
        $schedule->command('dian:enviar')->everyMinute()->withoutOverlapping();
        // Correos de fin de prueba (por vencer, vencida, último aviso) con constancia de envío
        // LocalPro gratis: 1 mes por cada emprendedor invitado que cumplió los pasos (antes de los avisos de plan)
        $schedule->command('localpro:referidos-meses')->dailyAt('07:50')->withoutOverlapping();
        $schedule->command('localpro:avisos-plan')->dailyAt('08:10')->withoutOverlapping();
        // Recordatorio a los contadores: declaraciones de sus clientes que vencen hoy o en 3 días
        $schedule->command('contabilidad:recordatorios')->dailyAt('07:20')->withoutOverlapping();
        // Prospección: busca firmas contables y su correo; envía la secuencia repartida en el día (lunes a viernes, 8 a 5)
        $schedule->command('prospectos:buscar')->dailyAt('06:30')->withoutOverlapping(60)->runInBackground();
        $schedule->command('prospectos:enviar')->weekdays()->everyFifteenMinutes()->between('08:00', '16:59')->withoutOverlapping(14);
        $schedule->command('prospectos:respuestas')->everyThirtyMinutes()->between('07:00', '21:00')->withoutOverlapping(25);
        // Negocios que terminaron la prueba sin pagar: se borran productos, imágenes, ventas… (quedan los datos de contacto)
        $schedule->command('localpro:depurar-pruebas')->dailyAt('04:30')->withoutOverlapping();
        // Comprime las imágenes pesadas que hayan quedado guardadas (libera espacio)
        $schedule->command('imagenes:optimizar --dias=2')->dailyAt('03:40')->withoutOverlapping(120)->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
