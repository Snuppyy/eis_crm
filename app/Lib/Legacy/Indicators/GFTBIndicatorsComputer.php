<?php

namespace App\Lib\Legacy\Indicators;

use DB;
use Carbon\Carbon;

use App\Models\User;

class GFTBIndicatorsComputer extends ProjectIndicatorsComputer
{
    public function compute($request, $customAccess, $new, $datesOnlyQuery, $datesQuery, $locationsQuery)
    {
        $gftbQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) use ($request) {
                $query->where('project_id', 12);

                if ($request->locations) {
                    $query->whereIn('location_id', $request->locations);
                }
            });

        $this->addDocumentsJoin($gftbQuery, 38, true);

        $gftbDSQuery = clone $gftbQuery;
        $gftbDSQuery->where(function ($query) {
            $query->where('d.data->byXk', 'SR4G')
                ->orWhere('d.data->byXk', 'vfCe');
        });

        $gftbMDRQuery = clone $gftbQuery;
        $gftbMDRQuery->where(function ($query) {
            $query->where('d.data->byXk', 'L3NT')
                ->orWhere('d.data->byXk', 'dX8x')
                ->orWhere('d.data->byXk', 'wqnc')
                ->orWhere('d.data->byXk', 'oJkE');
        });

        $gftbDetectedDSQuery = clone $gftbDSQuery;
        $gftbDetectedMDRQuery = clone $gftbMDRQuery;

        $gftbDetectedDSQuery->where('d.data->fB7S', 'WjFb');
        $gftbDetectedMDRQuery->where('d.data->fB7S', 'WjFb');

        if ($request->from) {
            $gftbDetectedDSQuery->where('d.data->Sbmr', '>=', $request->from);
            $gftbDetectedMDRQuery->where('d.data->Sbmr', '>=', $request->from);
        }

        if ($request->till) {
            $gftbDetectedDSQuery->where('d.data->Sbmr', '<=', $request->till);
            $gftbDetectedMDRQuery->where('d.data->Sbmr', '<=', $request->till);
        }

        $gftbDetected = $gftbDetectedDSQuery->count(DB::raw('distinct users.id'));
        $gftbDetectedMDR = $gftbDetectedMDRQuery->count(DB::raw('distinct users.id'));

        $gftbServicedQuery = clone $gftbQuery;

        $this->addDocumentsJoin($gftbServicedQuery, 39, false, true);

        $gftbServicedQuery->whereHas('activities', function ($query) use ($request) {
            $query->where('project_id', 12)
                ->whereIn('user_activity.part_id', [365, 367, 472, 474, 475, 616]);

            if ($request->till) {
                $query->where('start_date', '<=', $request->till);
            }
        });

        $gftbServicedDSQuery = clone $gftbServicedQuery;
        $gftbServicedDSQuery->where(function ($query) {
            $query->where('d39.data->HpTz', '5k3q')
                ->orWhere('d39.data->HpTz', 'ATRT');
        });

        $gftbServiced = $gftbServicedDSQuery->count(DB::raw('distinct users.id'));

        $gftbServicedMDRQuery = clone $gftbServicedQuery;
        $gftbServicedMDRQuery->where(function ($query) {
            $query->where('d39.data->HpTz', 'NNfH')
                ->orWhere('d39.data->HpTz', '9xF2')
                ->orWhere('d39.data->HpTz', 'uogs')
                ->orWhere('d39.data->HpTz', '7rYQ');
        });

        $gftbServicedMDR = $gftbServicedMDRQuery->count(DB::raw('distinct users.id'));

        $gftbTreatedQuery = clone $gftbQuery;
        $this->addDocumentsJoin($gftbTreatedQuery, 8, false, true);
        $this->addDocumentsJoin($gftbTreatedQuery, 39, false, true);

        $gftbTreatedQuery->whereNotNull('d8.data->SKEJ');

        if ($request->till) {
            $gftbTreatedQuery->where('d8.data->SKEJ', '<=', $request->till);
        }

        $gftbTreatedDSQuery = clone $gftbTreatedQuery;
        $gftbTreatedDSQuery->where(function ($query) {
            $query->where('d39.data->HpTz', '5k3q')
                ->orWhere('d39.data->HpTz', 'ATRT');
        });

        $gftbTreatedDS = $gftbTreatedDSQuery->count();

        $gftbTreatedMDRQuery = clone $gftbTreatedQuery;
        $gftbTreatedMDRQuery->where(function ($query) {
            $query->where('d39.data->HpTz', 'NNfH')
                ->orWhere('d39.data->HpTz', '9xF2')
                ->orWhere('d39.data->HpTz', 'uogs')
                ->orWhere('d39.data->HpTz', '7rYQ');
        });

        $gftbTreatedMDR = $gftbTreatedMDRQuery->count();

        $gftbTreatedDSAdherent = 0;
        $gftbTreatedMDRAdherent = 0;

        foreach ($gftbTreatedQuery->get() as $client) {
            if (!empty($client->profile['11']) && !empty($client->profile['11'][0]->data['TNF5'])) {
                $latestDate = null;
                $latestOutcome = null;
                $latestCause = null;
                $latestDeathCause = null;

                $outcomes = 0;

                foreach ($client->profile['11'] as $form) {
                    if (!empty($form->data['TNF5'])
                        && (!$request->till || $request->till >= $form->data['TNF5'])
                        && (!$request->from || $request->from <= $form->data['TNF5'])
                    ) {
                        $outcomes++;

                        if ($form->data['TNF5'] > $latestDate) {
                            $latestDate = $form->data['TNF5'];
                            $latestOutcome = $form->data['JbaL'];
                            $latestCause = $form->data['XcEm'] ?? null;
                            $latestDeathCause = $form->data['asmx'] ?? null;
                        }
                    }
                }

                $initiated = $client->profile['8'][0]->data['87Xt'];

                if (($latestOutcome == 'meHa' && ($latestCause == 'Lwch' || $latestCause == 'KyEj'))
                    || (!$outcomes
                        && (($request->from && $initiated < $request->from)
                            || ($request->till && $initiated > $request->till)))
                ) {
                    continue;
                }

                $from = $request->from ? Carbon::parse($request->from) : null;
                $inProjectFrom = Carbon::parse($initiated);

                $start = $from && $from > $inProjectFrom ? $from : $inProjectFrom;

                $till = !$request->till ? now() : Carbon::parse($request->till);
                $latest = Carbon::parse($latestDate);

                $end = !$latest || $latest > $till ? $till : $latest;

                $start->floorMonth();
                $end->ceilMonth();

                $months = (int) $start->floatDiffInMonths($end);

                if (($latestOutcome == 'nXDJ' ||
                    ($latestOutcome == 'meHa' &&
                        ($latestCause == 'Ze52' ||
                            ($latestCause == 'BtcT' && $latestDeathCause == 'S4s5'))) ||
                    ($latestOutcome == 'bJjG' && substr($latestDate, 0, 7) >=
                        (new Carbon($request->till ?: 'last month'))->format('Y-m'))) &&
                    $outcomes >= $months
                ) {
                    if (in_array($client->profile['38'][0]['HpTz'], ['5k3q', 'ATRT'])) {
                        $gftbTreatedDSAdherent++;
                    } elseif (in_array($client->profile['38'][0]['HpTz'], ['NNfH', '9xF2', 'uogs', '7rYQ'])) {
                        $gftbTreatedMDRAdherent++;
                    }
                }
            }
        }

        $gftbVSTSQuery = clone $gftbQuery;
        $this->addDocumentsJoin($gftbVSTSQuery, 8, false, true);
        $gftbVSTSQuery->where('d8.data->N5r3', true);
        $gftbVSTClients = $gftbVSTSQuery->count(DB::raw('distinct users.id'));

        return [
            [
                'title' => 'Показатели GFTB',
                'items' => [
                    [
                        [
                            $gftbDetected,
                            'Количество ЛЧ-ТБ больных выявленных при содействии сотрудников суб-суб проекта',
                            'clients?project=12&profileDate=38.Sbmr&profileField2=38.fB7S&profileValue2=WjFb' .
                            '&profileField=38.byXk&profileValue=SR4G&profileValue=vfCe&profileOp=or' .
                            $datesQuery . $locationsQuery
                        ],
                        [
                            $gftbDetectedMDR,
                            'Количество ЛУ-ТБ больных выявленных при содействии сотрудников суб-суб проекта',
                            'clients?project=12&profileDate=38.Sbmr&profileField2=38.fB7S&profileValue2=WjFb' .
                            '&profileField=38.byXk&profileValue=L3NT&profileValue=dX8x&profileValue=wqnc' .
                                '&profileValue=oJkE&profileOp=or' . $datesQuery . $locationsQuery
                        ]
                    ],
                    [
                        [
                            $gftbServiced,
                            'Количество ЛЧ-ТБ больных охваченных социально-психологическим сопровождением',
                            'clients?project=12' .
                            '&profileField=39.HpTz&profileValue=5k3q&profileValue=ATRT&profileOp=or' .
                            '&parts=365&parts=367&parts=472&parts=474&parts=475&parts=616' .
                            $datesQuery . $locationsQuery
                        ],
                        [
                            $gftbTreatedDS ? round($gftbTreatedDSAdherent / $gftbTreatedDS * 1000) / 10 . '%' : '0%',
                            'Доля ЛЧ-ТБ больных, охваченных социально-психологическим сопровождением, '.
                                'которые начали и продолжают лечение препаратами первого ряда'
                        ]
                    ],
                    [
                        [
                            $gftbServicedMDR,
                            'Количество ЛУ-ТБ больных охваченных социально-психологическим сопровождением',
                            'clients?project=12' .
                            '&profileField=39.HpTz&profileValue=NNfH&profileValue=9xF2&profileValue=uogs' .
                                '&profileValue=7rYQ&profileOp=or' .
                            '&parts=365&parts=367&parts=472&parts=474&parts=475&parts=616' .
                                $datesQuery . $locationsQuery,
                        ],
                        [
                            $gftbTreatedMDR ? round($gftbTreatedMDRAdherent / $gftbTreatedMDR * 1000) / 10 . '%' : '0%',
                            'Доля ЛУ-ТБ больных, охваченных социально-психологическим сопровождением, ' .
                                'которые начали и продолжают лечение препаратами первого ряда'
                        ]
                    ],
                    [
                        [
                            $gftbVSTClients,
                            'Охват ТБ больных видео контролируемым методом лечения',
                            'clients?project=12&profileField=8.N5r3&profileOp=true' . $locationsQuery
                        ]
                    ]
                ]
            ]
        ];
    }
}
