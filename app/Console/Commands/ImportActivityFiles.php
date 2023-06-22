<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Excel;

use App\Imports\RidesImport;
use App\Models\Activity;
use File;
use Storage;

class ImportActivityFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:import-activity-files {activity} {directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import activity files';

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
        $activity = Activity::findOrFail($this->argument('activity'));
        $forms = $activity->forms ?? [];

        if(empty($forms['files'])) {
            $forms['files'] = [];
        }

        foreach(Storage::files($this->argument('directory')) as $file) {
            $basename = basename($file);
            $path = "activities/$activity->id/$basename";

            Storage::copy($file, $path);

            $forms['files'][] = [
                'path' => $path,
                'name' => $basename,
                'type' => Storage::mimeType($file)
            ];
        }

        $activity->forms = $forms;
        $activity->timestamps = false;
        $activity->save();
}
}
