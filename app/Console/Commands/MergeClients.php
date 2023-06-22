<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Excel;

use App\Imports\MergeClients as MergeClientsImport;

class MergeClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:merge-clients {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename and merge clients from list';

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
            new MergeClientsImport(),
            $this->argument('file')
        );
    }
}
