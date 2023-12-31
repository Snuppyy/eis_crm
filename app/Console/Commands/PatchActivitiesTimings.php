<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Excel;

use App\Imports\PatchActivitiesTimingsImport;

class PatchActivitiesTimings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:patch-activities-timings {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Patch activities with timings';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Excel::import(
            new PatchActivitiesTimingsImport(),
            $this->argument('file')
        );
    }
}
