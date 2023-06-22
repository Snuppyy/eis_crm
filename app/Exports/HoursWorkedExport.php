<?php

namespace App\Exports;

use App\Models\User;
use Carbon\CarbonInterval;
use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HoursWorkedExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected $since;
    protected $till;

    protected $headers;
    protected $items;

    public function __construct($since, $till)
    {
        $this->since = $since;
        $this->till = $till;

        $timingsCondition = function ($query) {
            $query->where('verified', true)
                ->where(function ($query) {
                    $query->where('volunteering', true)
                        ->orWhereIn('part_id', [19, 23])
                        ->orWhere('project_id', 4);
                })
                ->whereNotNull('timing')
                ->where('timing', '>', 0);

            if ($this->since) {
                $query->whereDate('began_at', '>=', $this->since);
            }

            if ($this->till) {
                $query->whereDate('ended_at', '<=', $this->till);
            }
        };

        $this->headers = collect();

        $this->items = User::orderBy('name')
            ->whereHas('projectUserTimings', $timingsCondition)
            ->with(['projectUserTimings' => function ($query) use ($timingsCondition) {
                $timingsCondition($query);
                $query->select(
                    DB::raw('SUM(timing) sum_timing'),
                    DB::raw('DATE(began_at) `date`')
                )->groupBy('laravel_through_key', 'date');
            }])
            ->get()
            ->map(function ($item) {
                $hoursPerDay = $item->projectUserTimings->pluck('sum_timing', 'date')->map(function ($timing) {
                    return CarbonInterval::seconds($timing)->cascade()->format('%H:%I');
                });

                $this->headers = $this->headers->concat($hoursPerDay->keys())->unique();

                return $hoursPerDay->prepend($item->name, 'Сотрудник');
            });

        $this->headers = $this->headers->sort()->prepend('Сотрудник')->toArray();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function map($item): array
    {
        $row = [];

        foreach ($this->headers as $key) {
            $row[] = $item[$key] ?? '';
        }

        return $row;
    }
}
