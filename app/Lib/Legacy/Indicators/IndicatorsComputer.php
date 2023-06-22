<?php

namespace App\Lib\Legacy\Indicators;

class IndicatorsComputer
{
    public function compute($indicatorValue)
    {
        set_time_limit(0);

        $request = (object) [
            'locations' => $indicatorValue->location_ids ? explode(',', $indicatorValue->location_ids) : null,
            'users' => $indicatorValue->user_ids ? explode(',', $indicatorValue->user_ids) : null,
            'from' => $indicatorValue->period_start,
            'till' => $indicatorValue->period_end,
        ];

        $new = $request->from || $request->till;

        $customAccess = false;

        if (isset($request->users[0]) && $request->users[0] == 0) {
            $customAccess = true;
            $request->users = null;
        }

        $datesOnlyQuery = ($request->from ? '&from=' . $request->from : '') .
            ($request->till ? '&till=' . $request->till : '');

        $datesQuery = $datesOnlyQuery . ($request->users ? '&users=' . implode('&users=', $request->users) : '');

        $locationsQuery = $request->locations ? '&locations=' . implode('&locations=', $request->locations) : '';

        $filterEmtpy = true;

        switch ($indicatorValue->project_id) {
            case 6:
                if (!$request->till || $request->till > '2022-12-31') {
                    $computer = new ETBUIndicatorsComputer5;
                } elseif ($request->till > '2022-09-30') {
                    $computer = new ETBUIndicatorsComputer4;
                } elseif ($request->till > '2022-06-30') {
                    $computer = new ETBUIndicatorsComputer3;
                } elseif ($request->till > '2022-03-31') {
                    $computer = new ETBUIndicatorsComputer2;
                } else {
                    $computer = new ETBUIndicatorsComputer1;
                }

                break;

            case 7:
                $computer = new DVVIndicatorsComputer;
                break;

            case 11:
                $computer = new DVVEUIndicatorsComputer;
                break;

            case 12:
                $computer = new GFTBIndicatorsComputer;
                $filterEmtpy = false;
                break;
        }

        $computer->request = $request;
        $indicators = $computer->compute(
            $request,
            $customAccess,
            $new,
            $datesOnlyQuery,
            $datesQuery,
            $locationsQuery
        );

        if ($filterEmtpy) {
            $indicators = collect($indicators)->map(function ($group) {
                $group['items'] = collect($group['items'])
                    ->map(function ($row) {
                        return isset($row['title']) ? $row :
                            collect($row)->filter(function ($item) {
                                return !empty($item[0]);
                            })->map(function ($item) {
                                if ($item[0] == '-') {
                                    return array_slice($item, 0, 2);
                                } else {
                                    return $item;
                                }
                            })->values()->toArray();
                    })->filter()
                    ->map(function ($row, $index) use ($group) {
                        return (isset($row['title']) && (
                            !isset($group['items'][$index + 1]) ||
                            (isset($group['items'][$index + 1]['title']) &&
                                $group['items'][$index + 1]['level'] == $row['level'])
                        )) ? null : $row;
                    })->filter()->values();

                return $group;
            });
        }

        return [
            'data' => $indicators,
            'access' => $customAccess
        ];
    }
}
