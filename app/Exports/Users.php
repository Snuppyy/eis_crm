<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Users implements FromQuery, WithMapping, WithHeadings
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
        return [
            $item->id,
            $item->name,
            $item->phone,
            $item->activities->count() ?
                $item->activities->get(0)->user->name : '',
            $item->created_at
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'ФИО',
            'Телефон',
            'Сотрудник',
            'Добавлен'
        ];
    }
}
