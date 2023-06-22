<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Excel;

use App\Imports\PartsImport;

class ImportParts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:import-parts {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import client profiles';

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
            new PartsImport(),
            $this->argument('file')
        );
    }
}
