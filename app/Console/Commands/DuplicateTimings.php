<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Project;
use App\Models\Part;
use App\Models\Timing;
use App\Models\User;
use Doctrine\DBAL\Query;
use PDO;

class DuplicateTimings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:duplicate-timings {project} {position} {source-user} {since?} {until?} {months?} {volunteering?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Duplicate employee\'s timings';

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
        $source = User::findOrFail($this->argument('source-user'));

        $timingsQuery = Timing::whereHas('projectUser', function ($query) use ($source) {
            $query->where('project_id', $this->argument('project'))
                ->where('position', $this->argument('position'))
                ->where('user_id', $source->id);
        });

        if ($since = $this->argument('since')) {
            $timingsQuery->whereDate('began_at', '>=', $since);
        }

        if ($until = $this->argument('until')) {
            $timingsQuery->whereDate('began_at', '<=', $until);
        }

        if ($this->argument('volunteering')) {
            $timingsQuery->where('volunteering', 1);
        }

        $months = $this->argument('months');

        foreach ($timingsQuery->get() as $sourceTiming) {
            $timing = $sourceTiming->replicate();
            $timing->began_at = $sourceTiming->began_at->addMonths($months);
            $timing->ended_at = $sourceTiming->ended_at ?
                $sourceTiming->ended_at->addMonths($months) : $sourceTiming->ended_at;
            $timing->created_at = $sourceTiming->created_at->addMonths($months);
            $timing->updated_at = $sourceTiming->updated_at->addMonths($months);
            $timing->verified = false;
            $timing->frozen = false;
            $timing->frozen_at = null;
            $timing->save();
        }

        echo $timingsQuery->count() . " added.\n";

        return 0;
    }
}
