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
        $schedule->command('redes:publicar-programadas')->everyMinute()->withoutOverlapping();
        $schedule->command('dian:enviar')->everyMinute()->withoutOverlapping();
        // Correos de fin de prueba (por vencer, vencida, último aviso) con constancia de envío
        $schedule->command('localpro:avisos-plan')->dailyAt('08:10')->withoutOverlapping();
        // Negocios que terminaron la prueba sin pagar: se borran productos, imágenes, ventas… (quedan los datos de contacto)
        $schedule->command('localpro:depurar-pruebas')->dailyAt('04:30')->withoutOverlapping();
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
