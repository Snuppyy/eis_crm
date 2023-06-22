<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Project;
use App\Models\Part;
use App\Models\User;

class AddPart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:add-part {project} {description} {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add part to project employees';

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
        $type = $this->argument('type') ?: 0;
        $project = Project::findOrFail($this->argument('project'));

        $part = Part::firstOrCreate([
            'description' => $this->argument('description'),
            'type' => $type
        ]);

        $users = User::whereHas('parts', function ($query) use ($type, $project) {
                $query->where('type', $type)
                    ->where('project_id', $project->id);
        })
            ->whereDoesntHave('parts', function ($query) use ($part, $project) {
                $query->where('parts.id', $part->id)
                    ->where('project_id', $project->id);
            })
            ->with(['parts' => function ($query) use ($project) {
                $query->wherePivot('project_id', $project->id)
                    ->withPivot(['location_id']);
            }])
            ->get();

        foreach ($users as $user) {
            $user->parts()->attach($part->id, [
                'project_id' => $project->id,
                'location_id' => $user->parts[0]->pivot->location_id,
                'position' => $user->parts[0]->pivot->position
            ]);
        }

        echo count($users) . " added.\n";

        return 0;
    }
}
