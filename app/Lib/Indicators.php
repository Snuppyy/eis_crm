<?php

namespace App\Lib;

use App\Jobs\ComputeIndicatorValues;
use App\Models\IndicatorValue;
use DB;

class Indicators
{
    public static function invalidate($projectId)
    {
        IndicatorValue::whereProjectId($projectId)
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);

        IndicatorValue::whereProjectId($projectId)
            ->where('outdated', '!=', 1)
            ->update(['outdated' => true]);
    }

    public static function compute($fromJob = false)
    {
        $indicatorValue = IndicatorValue::whereOutdated(1)
            ->orderBy(DB::raw('project_id = 6'))
            ->orderBy(DB::raw('data != "[]"'))
            ->orderBy('last_viewed_at', 'desc')
            ->orderBy('views', 'desc')
            ->first();

        if (!$indicatorValue) {
            return;
        }

        $maxConcurrency = $indicatorValue->project_id != 6 ? 4 : (
            empty($indicatorValue->data) || $indicatorValue->last_viewed_at > now()->subMinutes(5)
                ? 3 : 1
        );

        if (DB::table('jobs')->count() > $maxConcurrency + ($fromJob ? 1 : 0)) {
            return;
        }

        ComputeIndicatorValues::dispatch($indicatorValue);
    }
}
