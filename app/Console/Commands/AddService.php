<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Project;
use App\Models\Part;
use App\Models\User;

class AddService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:add-service {project} {description}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add service to project';

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
        $project = Project::where('id', $this->argument('project'))->firstOrFail();

        $part = Part::firstOrCreate([
            'description' => $this->argument('description'),
            'type' => 0
        ]);

        $clients = User::whereHas('parts', function ($query) use ($project) {
            $query->where('type', 0)
                ->where('project_id', $project->id);
        })
        ->whereDoesntHave('parts', function ($query) use ($project, $part) {
            $query->where('parts.id', $part->id)
                ->where('project_id', $project->id);
        })
        ->get();

        foreach ($clients as $client) {
            $projectUser = $client->projectsUsers()->where('project_id', $project->id)->first();

            $client->parts()->attach($part->id, [
                'project_id' => $project->id,
                'location_id' => $projectUser->location_id
            ]);
        }

        echo count($clients) . " added.\n";

        return 0;
    }
}
