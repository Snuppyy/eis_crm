<?php

namespace App\Exports;

use App\Lib\Legacy\Indicators\ProjectIndicatorsComputer;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use App\Models\User;

use DB;

class Cases implements FromCollection, WithMapping, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $headers;
    protected $items;

    public function __construct()
    {
        $query = User::where('users.roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereJsonDoesntContain('d.data->6g6x', 'ab8e')
            ->whereJsonContains('d.data->6g6x', 'iNHa')
            ->whereNotNull('d.data->kB3w')
            ->whereNotNull('d.data->g459')
            ->whereNotNull('d.data->GWQS')
            ->leftJoin('user_user', 'user_user.user_id', '=', 'users.id')
            ->where(function ($query) {
                $keyword = 'шоу';

                $query->selectRaw('count(*)')
                    ->from('activities')
                    ->join('user_activity', 'user_activity.activity_id', '=', 'activities.id')
                    ->leftJoin('timings', 'timings.activity_id', '=', 'activities.id')
                    ->where(function ($query) {
                        $query->where('user_activity.user_id', DB::raw('users.id'))
                            ->orWhere('user_activity.user_id', DB::raw('user_user.related_user_id'));
                    })
                    ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373])
                    ->where('project_id', 6)
                    ->whereDate('d.data->kB3w', '<=', DB::raw('start_date'))
                    ->whereDate('d.data->GWQS', '>=', DB::raw('start_date'))
                    ->where(function ($query) use ($keyword) {
                        $query->whereNull('title')
                            ->orWhere('title', 'not like', "%$keyword%");
                    })
                    ->where(function ($query) use ($keyword) {
                        $query->whereNull('description')
                            ->orWhere('description', 'not like', "%$keyword%");
                    })
                    ->where(function ($query) use ($keyword) {
                        $query->whereNull('timings.comment')
                            ->orWhere('timings.comment', 'not like', "%$keyword%");
                    });
            }, '>=', 2)
            ->where(function ($query) {
                $keyword = 'диагностик';

                $query->selectRaw('count(*)')
                    ->from('activities')
                    ->join('user_activity', 'user_activity.activity_id', '=', 'activities.id')
                    ->leftJoin('timings', 'timings.activity_id', '=', 'activities.id')
                    ->where(function ($query) {
                        $query->where('user_activity.user_id', DB::raw('users.id'))
                            ->orWhere('user_activity.user_id', DB::raw('user_user.related_user_id'));
                    })
                    ->whereIn('user_activity.part_id', [367, 371, 375, 376])
                    ->where('project_id', 6)
                    ->whereDate('d.data->kB3w', '<=', DB::raw('start_date'))
                    ->whereDate('d.data->GWQS', '>=', DB::raw('start_date'))
                    ->where(function ($query) use ($keyword) {
                        $query->where('title', 'like', "%$keyword%")
                            ->orWhere('description', 'like', "%$keyword%")
                            ->orWhere('timings.comment', 'like', "%$keyword%");
                    });
            }, '>=', 1)
            ->where(function ($query) {
                $keyword = 'шоу';

                $query->selectRaw('count(*)')
                    ->from('activities')
                    ->join('user_activity', 'user_activity.activity_id', '=', 'activities.id')
                    ->leftJoin('timings', 'timings.activity_id', '=', 'activities.id')
                    ->where(function ($query) {
                        $query->where('user_activity.user_id', DB::raw('users.id'))
                            ->orWhere('user_activity.user_id', DB::raw('user_user.related_user_id'));
                    })
                    ->whereIn('user_activity.part_id', [367, 371, 375, 376])
                    ->where('project_id', 6)
                    ->whereDate('d.data->kB3w', '<=', DB::raw('start_date'))
                    ->whereDate('d.data->GWQS', '>=', DB::raw('start_date'))
                    ->where(function ($query) use ($keyword) {
                        $query->whereNull('title')
                            ->orWhere('title', 'not like', "%$keyword%");
                    })
                    ->where(function ($query) use ($keyword) {
                        $query->whereNull('description')
                            ->orWhere('description', 'not like', "%$keyword%");
                    })
                    ->where(function ($query) use ($keyword) {
                        $query->whereNull('timings.comment')
                            ->orWhere('timings.comment', 'not like', "%$keyword%");
                    });
            }, '>=', 3)
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
            ]);

        ProjectIndicatorsComputer::addDocumentsJoinWithRequest($query);

        $query->with(['relatedUsers', 'documents'])
            ->orderBy('name')
            ->groupBy('id');

        $this->headers = [];
        $this->items = [];

        foreach ($query->get() as $item) {
            if (empty($item->profile[6][0]->data['kB3w'])) {
                continue;
            }

            $soc = $item->activities
                ->concat($item->relatedUsers->pluck('activities')->flatten())
                ->filter(function ($activity) use ($item) {
                    return true;
                    return $item->profile[6][0]->data['kB3w'] <= $activity->start_date &&
                        (empty($item->profile[6][0]->data['nt6j']) ||
                            $item->profile[6][0]->data['nt6j'] >= $activity->start_date);
                })
                ->countBy(function ($item) {
                    return date('Y.m (С)', strtotime($item->start_date));
                })
                ->toArray();

            $psy = $item->activities2
                ->concat($item->relatedUsers->pluck('activities2')->flatten())
                ->filter(function ($activity) use ($item) {
                    return true;
                    return $item->profile[6][0]->data['kB3w'] <= $activity->start_date &&
                        (empty($item->profile[6][0]->data['nt6j']) ||
                            $item->profile[6][0]->data['nt6j'] >= $activity->start_date);
                })
                ->countBy(function ($item) {
                    return date('Y.m (П)', strtotime($item->start_date));
                })
                ->toArray();

            $this->items[] = array_merge(
                [
                    'ФИО' => $item->name,
                    'Родители/опекуны' => $item->relatedUsers->pluck('name')->join(', '),
                    'В проекте' => $item->profile[6][0]->data['kB3w'] ?? '',
                    'Стационар' => $item->profile[6][0]->data['g459'] ?? '',
                    'Выписка' => $item->profile[6][0]->data['GWQS'] ?? '',
                    'Амбулаторно' => $item->profile[6][0]->data['8Fg3'] ?? '',
                    'Завершение' => $item->profile[6][0]->data['nt6j'] ?? ''
                ],
                $soc,
                $psy
            );

            $this->headers = array_merge(
                $this->headers,
                array_diff(array_keys($soc), $this->headers),
                array_diff(array_keys($psy), $this->headers)
            );
        }
    }

    public function styles(Worksheet $sheet)
    {
        // $sheet->getStyle('B2')->getFont()->setBold(true);
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
        array_unshift($this->headers, 'Завершение');
        array_unshift($this->headers, 'Амбулаторно');
        array_unshift($this->headers, 'Выписка');
        array_unshift($this->headers, 'Стационар');
        array_unshift($this->headers, 'В проекте');
        array_unshift($this->headers, 'Родители/опекуны');
        array_unshift($this->headers, 'ФИО');

        return $this->headers;
    }
}
