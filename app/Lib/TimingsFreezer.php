<?php

namespace App\Lib;

use Auth;
use DB;
use Carbon\Carbon;

use App\Models\Activity;
use App\Models\Freeze;
use App\Models\Timing;

class TimingsFreezer
{
    public static function freeze($projectId, $userIds, Carbon $since, Carbon $till)
    {
        $creatorId = Auth::user() ? Auth::user()->id : 5;

        Timing::where('began_at', '>=', $since)
            ->where('began_at', '<=', $till)
            ->whereHas('projectUser.user', function ($query) use ($projectId, $userIds) {
                $query->whereIn('user_id', $userIds)
                    ->where('project_id', $projectId);
            })
            ->update([
                'frozen' => true,
                'frozen_at' => now(),
                'updated_at' => DB::raw('updated_at')
            ]);

        Activity::where('start_date', '>=', $since->toDateString())
            ->where('start_time', '>=', $since->toTimeString())
            ->where('start_date', '<=', $till->toDateString())
            ->where('start_time', '<=', $till->toTimeString())
            ->whereDoesntHave('timings')
            ->delete();

        foreach ($userIds as $userId) {
            Freeze::create([
                'creator_id' => $creatorId,
                'project_id' => $projectId,
                'user_id' => $userId,
                'start_at' => $since,
                'end_at' => $till
            ]);
        }
    }
}
