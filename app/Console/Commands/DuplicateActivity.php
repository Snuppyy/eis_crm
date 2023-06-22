<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Project;
use App\Models\Part;
use App\Models\User;
use Carbon\Carbon;
use Doctrine\DBAL\Query;
use PDO;

class DuplicateActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:duplicate-activity {position} {source-project} {source-user} {destination-project} {destination-user} {since} {until} {months?} {volunteering?} {removeOriginal?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Duplicate employee\'s activity';

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
        $destination = User::findOrFail($this->argument('destination-user'));

        $activitiesQuery = $source->activities()
            ->where('project_id', $this->argument('source-project'))
            ->whereHas('allUsers', function ($query) {
                $query->whereHas('projectUser', function ($query) {
                    $query->where('position', $this->argument('position'));
                });
            })
            ->withPivot(['role', 'part_id', 'project_user_id'])
            ->with('timings');

        if ($since = $this->argument('since')) {
            $activitiesQuery->where('start_date', '>=', $since);
        }

        if ($until = $this->argument('until')) {
            $activitiesQuery->where('start_date', '<=', $until);
        }

        if ($this->argument('volunteering')) {
            $activitiesQuery->where(function ($query) {
                $query->whereHas('allUsers', function ($query) {
                    $query->where('user_id', $this->argument('source-user'))
                        ->whereIn('part_id', [19, 23]);
                })
                ->orWhereHas('timings', function ($query) {
                    $query->where('volunteering', 1);
                });
            });
        }

        $activities = $activitiesQuery->get();

        $months = $this->argument('months') ?: 1;

        foreach ($activities as $sourceActivity) {
            $activity = $sourceActivity->replicate();
            $activity->project_id = $this->argument('destination-project');
            $activity->user_id = $destination->id;
            $activity->created_at = $sourceActivity->created_at;
            $activity->updated_at = $sourceActivity->updated_at;

            if ($months) {
                $activity->created_at = now();
                $activity->updated_at = now();

                $activity->start_date = Carbon::parse($activity->start_date)->addmonths($months)->format('Y-m-d');
                $activity->end_date = Carbon::parse($activity->end_date)->addmonths($months)->format('Y-m-d');
            }

            $activity->save();

            $projectUser = $destination->projectsUsers()
                ->where('project_id', $activity->project_id)
                ->where('part_id', $sourceActivity->pivot->part_id)
                ->first();

            if (!$projectUser) {
                die("No project_user for part {$sourceActivity->pivot->part_id}\n");
            }

            $projectUserId = $projectUser->id;

            $destination->activities()->attach($activity, [
                'role' => $sourceActivity->pivot->role,
                'part_id' => $sourceActivity->pivot->part_id,
                'project_user_id' => $projectUserId
            ]);

            $timingsQuery = $sourceActivity->timings()
                ->where('project_user_id', $sourceActivity->pivot->project_user_id);

            if ($this->argument('volunteering')) {
                $timingsQuery->where('volunteering', 1);
            }

            foreach ($timingsQuery->get() as $sourceTiming) {
                $timing = $sourceTiming->replicate();
                $timing->user_id = $destination->id;
                $timing->project_user_id = $projectUserId;
                $timing->activity_id = $activity->id;
                $timing->created_at = $sourceTiming->created_at;
                $timing->updated_at = $sourceTiming->updated_at;

                if ($months) {
                    $timing->created_at = now();
                    $timing->updated_at = now();
                    $timing->began_at = $timing->began_at->addMonths($months);
                    $timing->ended_at = $timing->ended_at->addMonths($months);
                }

                $timing->verified = false;
                $timing->frozen = false;
                $timing->frozen_at = null;
                $timing->flagged = true;
                $timing->flagged_at = now();
                $timing->flagged_by = 5;
                $timing->save();
            }

            if ($this->argument('removeOriginal')) {
                $sourceActivity->delete();
                $timingsQuery->delete();
            }
        }

        echo count($activities) . " added.\n";

        return 0;
    }
}
