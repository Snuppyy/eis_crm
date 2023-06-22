<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;

use App\Models\Part;
use App\Models\Activity;
use App\Models\ProjectUser;

class RestoreMissingParts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:restore-missing-parts {show-parts?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore missing parts for imported activities';

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
        $replaceParts = [
            76 => [
                371 => [
                    268 => 269
                ],
                362 => [
                    244 => 266
                ]
            ],
            353 => [
                365 => [
                    244 => 253
                ],
                361 => [
                    244 => 247
                ],
                362 => [
                    246 => 266
                ]
            ],
            82 => [
                361 => [
                    244 => 247
                ],
                362 => [
                    253 => 266
                ]
            ],
            59 => [
                365 => [
                    244 => 253
                ]
            ],
            73 => [
                365 => [
                    244 => 253
                ]
            ],
            72 => [
                368 => [
                    249 => 250
                ]
            ]
        ];

        $parts = [];

        $activities = Activity::whereHas('allUsers', function ($query) {
            $query->where('role', 'client');
        })
        ->whereDoesntHave('allUsers', function ($query) {
            $query->where('role', '!=', 'client');
        })
        ->with('allUsers')
        ->get();

        foreach ($activities as $activity) {
            if ($activity->allUsers->count() > 1) {
                echo "Multiple clients in {$activity->id}\n";
            }

            $userActivity = $activity->allUsers[0];

            if (!isset($parts[$activity->user_id][$userActivity->part_id])) {
                if (!isset($parts[$activity->user_id])) {
                    $parts[$activity->user_id] = [];
                }

                $reference = Activity::where('project_id', $activity->project_id)
                    ->whereHas('allUsers', function ($query) use ($userActivity) {
                        $query->where('role', 'client')
                            ->where('part_id', $userActivity->part_id);
                    })
                    ->whereHas('allUsers', function ($query) use ($activity) {
                        $query->where('role', '!=', 'client')
                            ->where('user_id', $activity->user_id);
                    })
                    ->with('allUsers', function ($query) use ($activity) {
                        $query->where('role', '!=', 'client');
                    })
                    ->first();

                if ($reference->allUsers->count() > 1) {
                    echo "Multiple implementers in {$activity->id}\n";
                }

                $parts[$activity->user_id][$userActivity->part_id] =
                    !empty($replaceParts[$activity->user_id][$userActivity->part_id][$reference->allUsers[0]->part_id]) ?
                        $replaceParts[$activity->user_id][$userActivity->part_id][$reference->allUsers[0]->part_id] :
                        $reference->allUsers[0]->part_id;

                if ($this->argument('show-parts')) {
                    $part1 = Part::find($userActivity->part_id);
                    $part2 = Part::find($parts[$activity->user_id][$userActivity->part_id]);

                    echo "{$activity->user_id}, {$userActivity->part_id}, {$parts[$activity->user_id][$userActivity->part_id]}\n{$part1->description}\n{$part2->description}\n";
                }
            }

            $projectUser = ProjectUser::where('user_id', $activity->user_id)
                ->where('project_id', $activity->project_id)
                ->where('part_id', $parts[$activity->user_id][$userActivity->part_id])
                ->first();

            if ($projectUser) {
                $activity->users()->attach($activity->user_id, [
                    'role' => 'implementer',
                    'part_id' => $parts[$activity->user_id][$userActivity->part_id],
                    'project_user_id' => $projectUser->id
                ], false);
            } else {
                echo "No project user for {$activity->user_id} and {$parts[$activity->user_id][$userActivity->part_id]}\n";
            }
        }

        echo "{$activities->count()} processed.\n";
    }
}
