<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exports\ClientsExport;
use Excel;

class ExportClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:export-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export clients';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Excel::store(new ClientsExport(), 'export/clients.xlsx');

        return 0;
    }
}
