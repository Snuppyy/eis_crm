<?php

namespace App\Exports;

use App\Lib\Legacy\Indicators\ProjectIndicatorsComputer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use App\Models\User;

use Carbon\Carbon;

class MDR implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    use Exportable;

    protected $headers;
    protected $items;

    public function __construct($request)
    {
        $query = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereNotNull('d.data->kB3w')
            ->orderBy('name')
            ->groupBy('users.id');

        ProjectIndicatorsComputer::addDocumentsJoinWithRequest($query, $request);

        if ($request->till) {
            $query->where('d.data->kB3w', '<=', $request->till);
        }

        ProjectIndicatorsComputer::addMDRClause($query);

        if ($request->has('nl')) {
            ProjectIndicatorsComputer::addNLClause($query);
        }

        $this->headers = [];
        $this->items = [];

        $outcomeLabels = [
            'bJjG' => 'Продолжает медикаментозное лечение ПТП',
            'nXDJ' => 'Успешно завершил медикаментозное лечение ПТП',
            'dAtv' => 'Потерян для последующего наблюдения',
            'meHa' => 'Снят с диспансерного учета',
            'EMsf' => 'Переход ЛЧТБ в МЛУТБ',
            'Ajf5' => 'Переход МЛУТБ в ШЛУТБ',
            'PnKg' => 'Продолжает ВСЛ',
            'A4Z9' => 'Не продолжает ВСЛ'
        ];

        $causeLabels = [
            'BtcT' => 'умер',
            'Lwch' => 'уехал',
            'KyEj' => 'попал в места исполнения наказания',
            'Ze52' => 'излечен'
        ];

        foreach ($query->get() as $client) {
            if (!empty($client->profile['11']) && !empty($client->profile['11'][0]->data['TNF5'])
                || (!empty($client->profile[52]) && !empty($client->profile[52][0]->data['TNF5']))
            ) {
                $items = [];

                $latestDate = null;
                $latestOutcome = null;
                $latestCause = null;
                $latestDeathCause = null;

                $outcomes = 0;

                $forms = $client->profile['11'] ?? collect();

                if (!empty($client->profile[52])) {
                    $forms = $forms->concat($client->profile[52])->sortByDesc('data.TNF5');
                }

                foreach ($forms as $form) {
                    if (!empty($form->data['TNF5'])
                        && (!$request->till || $request->till >= $form->data['TNF5'])
                        && (!$request->from || $request->from <= $form->data['TNF5'])
                    ) {
                        $outcomes++;

                        $items[substr($form->data['TNF5'], 0, 7)] = $outcomeLabels[$form->data['JbaL']]
                            . ($form->data['JbaL'] == 'meHa' && isset($form->data['XcEm']) ?
                                ' (' . $causeLabels[$form->data['XcEm']]
                                    . ($form->data['XcEm'] == 'BtcT' ? ($form->data['XcEm'] == 'S4s5' ?
                                        ', не ТБ' : ', ТБ') : '') . ')' : '')
                            . (empty($form->data['images']) ? ', нет изображения' : '')
                            . (!empty($form->data['verified']) ? ", проверено" : '');

                        if ($form->data['TNF5'] > $latestDate) {
                            $latestDate = $form->data['TNF5'];
                            $latestOutcome = $form->data['JbaL'];
                            $latestCause = $form->data['XcEm'] ??
                                (!empty($form->data['jBQK']) ? 'jBQK' : (!empty($form->data['F4fi']) ? 'F4fi' : null));
                            $latestDeathCause = $form->data['asmx'] ?? null;
                        }
                    }
                }

                $initiated = $client->profile['6'][0]->data['kB3w'];

                if (empty($client->profile['6'][0]->data['2CLW']) || $client->profile['6'][0]->data['2CLW'] != 'wx6s') {
                    if (!empty($client->profile['6'][0]->data['d6XS'])
                        && $client->profile['6'][0]->data['d6XS'] != 'vNSz'
                        && $client->profile['6'][0]->data['d6XS'] != 'MPCj'
                        && !empty($client->profile['6'][0]->data['it7x'])
                        && $client->profile['6'][0]->data['it7x'] > $initiated
                    ) {
                        $initiated = $client->profile['6'][0]->data['it7x'];
                    } elseif (!empty($client->profile['6'][0]->data['eNaZ'])
                        && $client->profile['6'][0]->data['eNaZ'] != 'muv9'
                        && $client->profile['6'][0]->data['eNaZ'] != 'EohW'
                        && !empty($client->profile['6'][0]->data['mynW'])
                        && $client->profile['6'][0]->data['mynW'] > $initiated
                    ) {
                        $initiated = $client->profile['6'][0]->data['mynW'];
                    }
                }

                if (!empty($client->profile['6'][0]->data['RB67'])
                    && $client->profile['6'][0]->data['RB67'] > $initiated
                ) {
                    $initiated = $client->profile['6'][0]->data['RB67'];
                }

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

                if (!empty($client->profile['6'][0]->data['nt6j']) && !empty($client->profile['6'][0]->data['fofk'])) {
                    $months -= Carbon::parse($client->profile['6'][0]->data['nt6j'])
                        ->ceilMonth()
                        ->floatDiffInMonths(Carbon::parse($client->profile['6'][0]->data['fofk'])->floorMonth());
                }

                $remained = false;
                if (($latestOutcome == 'nXDJ' ||
                    ($latestOutcome == 'meHa' &&
                        ($latestCause == 'Ze52' ||
                            ($latestCause == 'BtcT' && $latestDeathCause == 'S4s5'))) ||
                    ($latestOutcome == 'A4Z9' && ($latestCause == 'jBQK' || $latestCause == 'F4fi')) ||
                    (($latestOutcome == 'bJjG' || $latestOutcome == 'PnKg') && substr($latestDate, 0, 7) >=
                        (new Carbon($request->till ?: 'last month'))->format('Y-m'))) &&
                    $outcomes >= $months
                ) {
                    $remained = true;
                }

                $this->items[] = array_merge(
                    [
                        'ФИО' => $client->name,
                        'Лечится/выздоровел' => $remained ? 'Да' : '',
                        'Учреждение стационарного лечения' => !empty($client->profile[6][0]->data['8Src']) ? [
                                'nRns' => 'РСНЦФиП',
                                'u2Ea' => 'ДГФиП',
                                '6KMj' => 'ГКБФиП',
                                '98GN' => 'НИИ Вирусологии',
                            ][$client->profile[6][0]->data['8Src']] : '',
                        'В проекте' => $client->profile[6][0]->data['kB3w'] ?? '',
                        'Начало лечения' => $client->profile[6][0]->data['Q5xs'] ?? '',
                        'Госпитализация' => $client->profile[6][0]->data['g459'] ?? '',
                        'Выписка' => $client->profile[6][0]->data['GWQS'] ?? '',
                        'Завершение' => $client->profile[6][0]->data['nt6j'] ?? '',
                    ],
                    $items
                );

                $this->headers = array_merge(
                    $this->headers,
                    array_diff(array_keys($items), $this->headers),
                );
            }
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
            $row[] = $item[$key] ?? '';
        }

        return $row;
    }

    public function headings(): array
    {
        sort($this->headers);

        array_unshift($this->headers, 'Завершение');
        array_unshift($this->headers, 'Выписка');
        array_unshift($this->headers, 'Госпитализация');
        array_unshift($this->headers, 'Начало лечения');
        array_unshift($this->headers, 'В проекте');
        array_unshift($this->headers, 'Учреждение стационарного лечения');
        array_unshift($this->headers, 'Лечится/выздоровел');
        array_unshift($this->headers, 'ФИО');

        return $this->headers;
    }
}
