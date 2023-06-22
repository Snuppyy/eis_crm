<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exports\HoursWorkedExport;
use Excel;

class ExportHoursWorked extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:export-hours-worked {since} {till} {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export hours worked';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Excel::store(
            new HoursWorkedExport($this->argument('since'), $this->argument('till')),
            'export/' . $this->argument('filename')
        );

        return 0;
    }
}
