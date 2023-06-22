<?php

namespace App\Exports;

use App\Lib\Legacy\Indicators\ProjectIndicatorsComputer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MinimalCases implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    use Exportable;

    protected $headers;
    protected $items;

    public function __construct(Request $request, $dateLimit = false)
    {
        $query = User::where('users.roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereJsonDoesntContain('d.data->6g6x', 'ab8e')
            ->whereJsonContains('d.data->6g6x', 'iNHa')
            ->whereNotNull('d.data->kB3w')
            ->whereNotNull('d.data->g459')
            ->where('d.data->Rchw', true)
            ->with([
                'activities' => function ($query) {
                    $keyword = 'шоу';

                    $query->where('project_id', 6)
                        ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373])
                        ->where(function ($query) use ($keyword) {
                            $query->whereNull('title')
                                ->orWhere('title', 'not like', "%$keyword%");
                        })
                        ->where(function ($query) use ($keyword) {
                            $query->whereNull('description')
                                ->orWhere('description', 'not like', "%$keyword%");
                        })
                        ->whereDoesntHave('timings', function ($query) use ($keyword) {
                            $query->where('comment', 'like', "%$keyword%");
                        });
                },
                'activities2' => function ($query) {
                    $query->where('project_id', 6)
                        ->whereIn('user_activity.part_id', [367, 371, 375, 376]);
                },
                'relatedUsers.activities' => function ($query) {
                    $keyword = 'шоу';

                    $query->where('project_id', 6)
                        ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373])
                        ->where(function ($query) use ($keyword) {
                            $query->whereNull('title')
                                ->orWhere('title', 'not like', "%$keyword%");
                        })
                        ->where(function ($query) use ($keyword) {
                            $query->whereNull('description')
                                ->orWhere('description', 'not like', "%$keyword%");
                        })
                        ->whereDoesntHave('timings', function ($query) use ($keyword) {
                            $query->where('comment', 'like', "%$keyword%");
                        });
                },
                'relatedUsers.activities2' => function ($query) {
                    $query->where('project_id', 6)
                        ->whereIn('user_activity.part_id', [367, 371, 375, 376]);
                }
            ])
            ->with(['relatedUsers', 'documents'])
            ->orderBy('name');

        ProjectIndicatorsComputer::addDocumentsJoinWithRequest($query);

        $this->headers = [];
        $this->items = [];

        foreach ($query->get() as $item) {
            if (empty($item->profile[6][0]->data['kB3w'])) {
                continue;
            }

            $from = $request->from ? Carbon::parse($request->from) : null;
            $inProjectFrom = Carbon::parse($item->profile[6][0]->data['kB3w']);
            $treatmentStart = empty($item->profile[6][0]->data['Q5xs']) ? null :
                Carbon::parse($item->profile[6][0]->data['Q5xs']);
            $serviceStart = $treatmentStart && $treatmentStart > $inProjectFrom ? $treatmentStart : $inProjectFrom;

            $start = $from && $from > $serviceStart ? $from : $serviceStart;

            $till = !$request->till ? now() : Carbon::parse($request->till);
            $treatmentEnd = !empty($item->profile[6][0]->data['nt6j'])
                ? Carbon::parse($item->profile[6][0]->data['nt6j']) : null;

            $end = !$treatmentEnd || $treatmentEnd > $till ? $till : $treatmentEnd;

            if ($start->day > 24) {
                $start->ceilMonth();
            } else {
                $start->floorMonth();
            }

            if ($end->day < 6) {
                $end->floorMonth();
            } else {
                $end->ceilMonth();
            }

            $months = (int) $start->floatDiffInMonths($end);

            $end->subSecond();

            $soc = $item->activities
                ->concat($item->relatedUsers->pluck('activities')->flatten())
                ->filter(function ($activity) use ($start, $end) {
                    return $start <= Carbon::parse($activity->start_date) &&
                        $end >= Carbon::parse($activity->start_date);
                })
                ->countBy(function ($item) {
                    return date('Y.m (С)', strtotime($item->start_date));
                })
                ->toArray();

            $psy = $item->activities2
                ->concat($item->relatedUsers->pluck('activities2')->flatten())
                ->filter(function ($activity) use ($start, $end) {
                    return $start <= Carbon::parse($activity->start_date) &&
                        $end >= Carbon::parse($activity->start_date);
                })
                ->countBy(function ($item) {
                    return date('Y.m (П)', strtotime($item->start_date));
                })
                ->toArray();

            $itemToAdd = false;

            if ($dateLimit) {
                if (!empty($item->profile[6][0]->data['GWQS'])) {
                    $end2 = Carbon::parse($item->profile[6][0]->data['GWQS']);

                    if ($end2->day < 6) {
                        $end2->floorMonth();
                    } else {
                        $end2->ceilMonth();
                    }

                    $months2 = (int) $start->floatDiffInMonths($end2);

                    $end2->subSecond();

                    $soc2 = $item->activities
                        ->concat($item->relatedUsers->pluck('activities')->flatten())
                        ->filter(function ($activity) use ($start, $end2) {
                            return $start <= Carbon::parse($activity->start_date) &&
                                $end2 >= Carbon::parse($activity->start_date);
                        })
                        ->countBy(function ($item) {
                            return date('Y.m (С)', strtotime($item->start_date));
                        })
                        ->toArray();

                    $psy2 = $item->activities2
                        ->concat($item->relatedUsers->pluck('activities2')->flatten())
                        ->filter(function ($activity) use ($start, $end2) {
                            return $start <= Carbon::parse($activity->start_date) &&
                                $end2 >= Carbon::parse($activity->start_date);
                        })
                        ->countBy(function ($item) {
                            return date('Y.m (П)', strtotime($item->start_date));
                        })
                        ->toArray();

                    if (count($soc2) && count($psy2) && count($soc2) >= $months2 && count($psy2) >= $months2) {
                        $itemToAdd = $item;
                    }
                }
            } elseif (count($soc) && count($psy) && count($soc) >= $months && count($psy) >= $months) {
                $itemToAdd = true;
            }

            $this->items[] = array_merge(
                [
                    'ФИО' => $item->name,
                    'Получил/получает пакет' => $itemToAdd ? 'да' : '',
                    'Родители/опекуны' => $item->relatedUsers->pluck('name')->join(', '),
                    'Место предоставления услуг' => !empty($item->profile[6][0]->data['6uM3']) ? [
                            '6Dr8' => 'Аутрич',
                            'DqoT' => 'МФД-4',
                            'J6h6' => 'ГКБФиП',
                            'X5Zx' => 'МФД-2',
                            'ZqZj' => 'МФД-1',
                            'ebBT' => 'МФД-3',
                            'mjSk' => 'РСНПЦФиП',
                            'qPcj' => 'МФД-5',
                            'wofi' => 'ДГФБ',
                            'wpBE' => 'ТГЦФиП'
                        ][$item->profile[6][0]->data['6uM3']] : '',
                    'Учреждение стационарного лечения' => !empty($item->profile[6][0]->data['8Src']) ? [
                            'nRns' => 'РСНЦФиП',
                            'u2Ea' => 'ДГФиП',
                            '6KMj' => 'ГКБФиП',
                            '98GN' => 'НИИ Вирусологии',
                        ][$item->profile[6][0]->data['8Src']] : '',
                    'Учреждение амбулаторного лечения' => !empty($item->profile[6][0]->data['fFQK']) ? [
                            'vdcj' => 'ТГЦФиП',
                            'nP8X' => 'МФД-1',
                            'W8QW' => 'МФД-2',
                            'LFys' => 'МФД-3',
                            'uSng' => 'МФД-4',
                            'qSf5' => 'МФД-5'
                        ][$item->profile[6][0]->data['fFQK']] : '',
                    'В проекте' => $item->profile[6][0]->data['kB3w'] ?? '',
                    'Стационар' => $item->profile[6][0]->data['g459'] ?? '',
                    'Выписка' => $item->profile[6][0]->data['GWQS'] ?? '',
                    'Амбулаторно' => $item->profile[6][0]->data['8Fg3'] ?? '',
                    'Завершение' => $item->profile[6][0]->data['nt6j'] ?? '',
                    'Лечение завершено' => !empty($item->profile[6][0]->data['nt6j']) && now() >= Carbon::parse($item->profile[6][0]->data['nt6j']) ? 'Завершено' : '',
                ],
                $soc,
                $psy,
            );

            $this->headers = array_merge(
                $this->headers,
                array_diff(array_keys($soc), $this->headers),
                array_diff(array_keys($psy), $this->headers)
            );
        }
    }

    public function collection()
    {
        return collect($this->items);
    }

    public function map($item): array
    {
        $row = [];

        foreach ($this->headers as $key) {
            $row[] = $item[$key] ?? '0';
        }

        return $row;
    }

    public function headings(): array
    {
        sort($this->headers);
        array_unshift($this->headers, 'Лечение завершено');
        array_unshift($this->headers, 'Завершение');
        array_unshift($this->headers, 'Амбулаторно');
        array_unshift($this->headers, 'Выписка');
        array_unshift($this->headers, 'Стационар');
        array_unshift($this->headers, 'В проекте');
        array_unshift($this->headers, 'Учреждение амбулаторного лечения');
        array_unshift($this->headers, 'Учреждение стационарного лечения');
        array_unshift($this->headers, 'Место предоставления услуг');
        array_unshift($this->headers, 'Родители/опекуны');
        array_unshift($this->headers, 'Получил/получает пакет');
        array_unshift($this->headers, 'ФИО');

        return $this->headers;
    }
}
