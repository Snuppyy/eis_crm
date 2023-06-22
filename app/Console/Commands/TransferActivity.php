<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

use App\Models\User;

class TransferActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:transfer-activity {project} {position} {source-user} {destination-user} {since?} {until?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer employee\'s activity to another employee';

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
        $projectId = $this->argument('project');
        $source = User::findOrFail($this->argument('source-user'));
        $destination = User::findOrFail($this->argument('destination-user'));

        $activitiesQuery = $source->activities()
            ->whereHas('allUsers', function ($query) use ($projectId) {
                $query->whereHas('projectUser', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId)
                        ->where('position', $this->argument('position'));
                });
            })
            ->withPivot(['role', 'part_id', 'project_user_id'])
            ->with(['timings' => function ($query) use ($source) {
                $query->whereHas('projectUser', function ($query) use ($source) {
                    $query->where('user_id', $source->id)
                        ->where('position', $this->argument('position'));
                });
            }]);

        if ($since = $this->argument('since')) {
            $activitiesQuery->where('start_date', '>=', $since);
        }

        if ($until = $this->argument('until')) {
            $activitiesQuery->where('start_date', '<=', $until);
        }

        $activities = $activitiesQuery->get();

        foreach ($activities as $activity) {
            $pivot = $activity->pivot;

            $projectUser = $destination
                ->projectsUsers()
                ->where('project_id', $projectId)
                ->where('part_id', $pivot->part_id)
                ->where('position', $pivot->projectUser->position)
                ->first();

            if (!$projectUser) {
                die("No project_user for part {$pivot->part_id} ({$pivot->position})\n");
            }

            if ($activity->user_id != $source->id) {
                echo $activity->id . ',';
            }

            $projectUserId = $projectUser->id;

            $destination->activities()->attach($activity, [
                'role' => $pivot->role,
                'part_id' => $pivot->part_id,
                'project_user_id' => $projectUserId
            ]);

            foreach ($activity->timings as $timing) {
                $timing->user_id = $destination->id;
                $timing->project_user_id = $projectUserId;
                $timing->timestamps = false;
                $timing->save();
            }

            $source->activities()->detach($activity);
        }

        $activities = Activity::where('user_id', $source->id)
            ->where('project_id', $projectId)
            ->get();

        foreach ($activities as $activity) {
            $activity->user_id = $destination->id;
            $activity->timestamps = false;
            $activity->save();
        }

        echo count($activities) . " activities transferred.\n";
    }
}
