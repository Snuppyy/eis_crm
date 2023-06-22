<?php

namespace App\Console;

use App\Lib\Etc;
use App\Lib\TimingsFreezer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        /* $schedule->call(function () {
            $till = now()->subDay()->setTime(18, 0);
            $since = clone $till;
            $since->subDay();

            TimingsFreezer::freeze(6, array_slice(Etc::$project6serviceEmployees, 1), $since, $till);
        })->dailyAt('18:00'); */
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
