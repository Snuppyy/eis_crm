<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Training implements FromQuery, WithMapping, WithHeadings
{
    protected $query;

    public function __construct($query, $project = 7)
    {
        $query->with(['documents' => function ($query) use ($project) {
            $query->where('form_id', 51)
                ->where('data->tdak', $project == 7 ? 'gZk8' : '7ij9');
        }]);

        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function map($item): array
    {
        return [
            $item->name,
            [
                '-'    => '-',
                'mzkt' => 'Электрогазосварщик',
                'ZXH3' => 'Электросварщик',
                '7tuh' => 'Электросварщик на полуавтоматических машинах (Кемпи)',
                'QMKD' => 'Электромонтёр',
                '7t84' => 'Швея - Партной',
                'q5CD' => 'Бухгалтер',
                'DqXs' => 'Пошив штор и занавесок',
                'vCmN' => 'Массажист',
                'JsN6' => 'Оператор компьютера',
                'uzAS' => 'Техник по обслуживанию компьютеров',
                'w7Ez' => 'Парикмахер',
                'Dh9x' => 'Слесарь-сантехник',
                'dAK8' => 'Электромеханик по ремонту бытового электрооборудования',
                'g3Es' => 'Специалист по SMM рекламе',
                '5ecp' => 'Повар-Кондитер',
            ][$item->profile[51][0]->data['zDZL'] ?? '-'],
            $item->profile[51][0]->data['7Amw'],
            $item->profile[51][0]->data['iXCk'] ?? '-'
        ];
    }

    public function headings(): array
    {
        return [
            'ФИО',
            'Профессия',
            'Начало',
            'Окончание'
        ];
    }
}
