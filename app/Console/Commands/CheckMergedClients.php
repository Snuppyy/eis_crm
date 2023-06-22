<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Excel;

use App\Imports\CheckMergedClients as CheckMergedClientsImport;

class CheckMergedClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:check-merged-clients {file} {collapseWhitespace?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check merged clients from list for possible corruption';

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
            new CheckMergedClientsImport($this->argument('collapseWhitespace')),
            $this->argument('file')
        );
    }
}
