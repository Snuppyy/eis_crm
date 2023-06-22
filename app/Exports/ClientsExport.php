<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ClientsExport implements FromQuery, WithMapping, WithHeadings, WithColumnFormatting
{
    use Exportable;

    public function query()
    {
        return User::whereRaw('FIND_IN_SET("client",roles)')
            ->with(['activities'])
            ->orderBy('name');
    }

    public function map($item): array
    {
        return [
            $item->id,
            $item->name,
            '',
            $item->activities->count() ?
                $item->activities->last()->user->name : '',
            $item->created_at
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Имя',
            'Настоящее имя',
            'Сотрудник',
            'Дата регистрации'
        ];
    }

    public function columnFormats(): array
    {
        return [
        ];
    }
}
