<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Etc;
use App\Lib\Indicators;
use App\Models\IndicatorValue;
use Illuminate\Http\Request;

class IndicatorsController extends Controller
{
    public function index(Request $request)
    {
        if (empty($request->users)) {
            if (in_array($request->user()->id, Etc::$customIndicatorsAccessUsers)) {
                $request->users = [0];
            } elseif (!in_array('superuser', $request->user()->roles)) {
                $request->users = [$request->user()->id];
            }
        }

        $query = [
            'project_id' => $request->project,
            'location_ids' => $request->locations ? collect($request->locations)->sort()->join(',') : null,
            'user_ids' => $request->users ? collect($request->users)->sort()->join(',') : null,
            'period_start' => $request->from,
            'period_end' => $request->till
        ];

        $value = IndicatorValue::firstOrCreate($query);

        if ($value->wasRecentlyCreated) {
            $value->outdated = true;
        }

        if (!$value->outdated) {
            $value->views++;
        }

        $value->last_viewed_at = now();
        $value->timestamps = false;
        $value->save();

        $result = $value->data;
        $result['id'] = $value->id;
        $result['outdated'] = $value->outdated;
        $result['valid_at'] = $value->invalidated_at;

        Indicators::compute();

        return $result;
    }
}
