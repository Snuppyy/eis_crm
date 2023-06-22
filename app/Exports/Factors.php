<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Factors implements FromCollection, WithHeadings
{
    use Exportable;

    protected $items;

    public function __construct()
    {
        $documentClause = function ($query) {
            $query->where('form_id', 55);
        };

        $this->items = User::orderBy('name')
            ->whereHas('documents', $documentClause)
            ->with(['documents' => $documentClause])
            ->get()
            ->map(function ($item) {
                return empty($item->profile[55][0]->data['results']) ? null : [
                    $item->name,
                    $item->profile[55][0]->data['results'][0]['value'],
                    $item->profile[55][0]->data['results'][1]['value'],
                    $item->profile[55][0]->data['results'][2]['value'],
                    $item->profile[55][0]->data['results'][3]['value'],
                    $item->profile[55][0]->data['results'][4]['value'],
                    $item->profile[55][0]->data['results'][5]['value'],
                    $item->profile[55][0]->data['results'][6]['value']
                ];
            })
            ->filter();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'Сотрудник',
            'Образование',
            'Опыт работы',
            'Язык',
            'Программное обеспечение и оргтехника',
            'Сертификаты',
            'Сумма баллов',
            'Расчётная зарплата'
        ];
    }
}
