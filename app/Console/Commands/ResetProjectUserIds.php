<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class ResetProjectUserIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:reset-project_user_ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset activity_user project_user_ids';

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
        DB::table('user_activity as ua')
            ->join('activities as a', 'id', '=', 'ua.activity_id')
            ->join('project_user as pu', function ($query) {
                $query->on('pu.user_id', '=', 'ua.user_id')
                    ->on('pu.project_id', '=', 'a.project_id')
                    ->on('pu.part_id', '=', 'ua.part_id');
            })
            ->update([
                'project_user_id' => DB::raw('pu.id')
            ]);
    }
}
