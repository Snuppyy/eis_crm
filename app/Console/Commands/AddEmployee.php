<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Lib\Positions;

use App\Models\Project;
use App\Models\Part;
use App\Models\User;

class AddEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:add-employee {location} {project} {employee} {position}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add employee to project';

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
        $project = Project::findOrFail($this->argument('project'));
        $employee = User::where('id', $this->argument('employee'))
            ->orWhere('email', $this->argument('employee'))
            ->firstOrFail();

        Positions::addPosition($employee, $this->argument('location'), $project->id, $this->argument('position'));

        echo "Done.\n";

        return 0;
    }
}
