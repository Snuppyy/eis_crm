<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Services implements FromQuery, WithMapping, WithHeadings
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function map($item): array
    {
        $userActivities = $item->allUsers
            ->filter(function ($item) {
                return $item->role == 'client';
            });

        return [
            $item->id,
            $userActivities->map(function ($item) {
                return $item->user->name;
            })->join(', '),
            $userActivities->map(function ($item) {
                return $item->part->description;
            })->join(', '),
            $item->title,
            $item->timings->map(function ($item) {
                return $item->comment;
            })->join('; '),
            $item->user->name,
            $item->start_date
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Клиент',
            'Услуга',
            'Описание',
            'Комментарий',
            'Сотрудник',
            'Дата'
        ];
    }
}
