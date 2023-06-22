<?php

namespace App\Lib;

use Carbon\Carbon;

class DocumentResults
{
    public static function compute($formId, $data)
    {
        if ($formId == 55) {
            return static::computeEmployeeScore($data);
        }
    }

    private static function computeEmployeeScore($data)
    {
        $score1 = 0;

        if (!empty($data['cr3q'])) {
            $score1 += [
                'SCu4' => 1,
                'vgDr' => 3 + 1,
                'MLXe' => 5 + 3 + 1,
                'saoT' => 1 + 5 + 3 + 1,
                '9476' => 1 + 1 + 5 + 3 + 1,
            ][$data['cr3q']];
        }

        if (!empty($data['jEfE']) && $data['jEfE'] != 'SCu4') {
            $score1 += array_search($data['jEfE'], ['SCu4', 'pQtr', 'QSdG', '3RL3']) * 2;

            if (!empty($data['Wuv7'])) {
                $score1 += 2;
            }
        }

        if (!empty($data['TW5f']) && is_array($data['TW5f'])) {
            foreach ($data['TW5f'] as $index => $item) {
                $score1 += array_search($item, ['SCu4', 'mR3s', 'ndat', 'PgCx', 'Pqsp', 'Fw4G', 'iCDS', '9exx']) * 4;

                if (!empty($data['PWku'][$index])) {
                    $score1 += 4;
                }
            }
        }

        if (!empty($data['MDPu']) && $data['MDPu'] != 'SCu4') {
            $score1 += array_search($data['MDPu'], ['SCu4', '2kCA', 'kN8k', 'nKF7']) * 5;

            if (!empty($data['FzKs'])) {
                $score1 += 5;
            }
        }

        if (!empty($data['CK7f']) && $data['CK7f'] != 'SCu4') {
            $score1 += array_search($data['CK7f'], ['SCu4', 'CTiH', 'FHL7', 'wWRR']) * 5;
        }

        if (!empty($data['LAxW']) && $data['LAxW'] != 'SCu4') {
            $score1 += array_search($data['LAxW'], ['SCu4', '8fWq', 'AF7v', 'ASgz', 'dgQv']) * 5;
        }

        if (!empty($data['FSnx']) && $data['FSnx'] != 'SCu4') {
            $score1 += array_search($data['FSnx'], ['SCu4', 'XLcg', 'oJtb', '8Dcr', 'NrXK', 'dSoq']) * 5;
        }

        if (!empty($data['6SiW']) && $data['6SiW'] == true) {
            $score1 *= 1.5;
        }

        $score2 = 0;

        if (!empty($data['QrJp']) && is_array($data['QrJp'])) {
            foreach ($data['QrJp'] as $index => $QrJp) {
                if (!empty($QrJp)) {
                    $suqB = empty($data['suqB'][$index]) ? now() : Carbon::parse($data['suqB'][$index]);
                    $score2 += Carbon::parse($QrJp)->floatDiffInMonths($suqB) * (2 / 12);
                }
            }
        }

        if (!empty($data['qCi3']) && is_array($data['qCi3'])) {
            $months = 0;

            foreach ($data['qCi3'] as $index => $qCi3) {
                if (!empty($qCi3)) {
                    $B9Ge = empty($data['B9Ge'][$index]) ? now() : Carbon::parse($data['B9Ge'][$index]);
                    $months += Carbon::parse($qCi3)->floatDiffInMonths($B9Ge);
                }
            }

            $score2 += ($months > 5 * 12 ? 5 : $months / 12) * 5;
            $score2 += ($months > 10 * 12 ? 5 : max(0, $months / 12 - 5)) * 10;
            $score2 += ($months > 20 * 12 ? 10 : max(0, $months / 12 - 10)) * 15;
            $score2 += ($months > 20 * 12 ? max(0, $months / 12 - 20) : 0) * 20;
        }

        $score2 = round($score2 * 10) / 10;

        $score3 = 0;

        if (!empty($data['j2PN'])) {
            $score3 += [3, 5, 7, 9, 11, 13, 15, 17][array_search($data['j2PN'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        if (!empty($data['Dhrz'])) {
            $score3 += [3, 5, 7, 9, 11, 13, 15, 17][array_search($data['Dhrz'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        if (!empty($data['hFpB'])) {
            $score3 += [2, 4, 5, 6, 7, 8, 9, 10][array_search($data['hFpB'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        if (!empty($data['t4ej'])) {
            $score3 += [2, 4, 5, 6, 7, 8, 9, 10][array_search($data['t4ej'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        if (!empty($data['tqcX'])) {
            $score3 += [2, 4, 5, 6, 7, 8, 9, 10][array_search($data['tqcX'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        if (!empty($data['tz5k'])) {
            $score3 += [4, 6, 8, 10, 12, 14, 16, 18][array_search($data['tz5k'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        if (!empty($data['6HhE'])) {
            $score3 += [4, 6, 8, 10, 12, 14, 16, 18][array_search($data['6HhE'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        if (!empty($data['iSDN'])) {
            $score3 += [4, 6, 8, 10, 12, 14, 16, 18][array_search($data['iSDN'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        if (!empty($data['pH7z'])) {
            $score3 += [1, 3, 4, 5, 6, 7, 8, 9][array_search($data['pH7z'], [
                'T9rM', 'zqNW', 'PXKx', 'SfM3', 'nCa9', 'WCXZ', 'ZwWf', 'RE4X'
            ])];
        }

        $score4 = 0;

        if (!empty($data['E9f8'])) {
            $score4 += [0, 1, 2, 3, 4, 5][array_search($data['E9f8'], [
                '4feX', '8Dit', 'Cec2', 'h68g', 'pshe', 'DeAQ'
            ])];
        }

        if (!empty($data['e8ii'])) {
            $score4 += [0, 1, 2, 3, 4, 5][array_search($data['e8ii'], [
                'B9bQ', 'wAt9', 'rfaJ', 'hNDK', 'qfRQ', 'k3sZ'
            ])];
        }

        if (!empty($data['XioR'])) {
            $score4 += [0, 1, 2, 3, 4, 5][array_search($data['XioR'], [
                'gWuu', 'zTta', '7HvS', 'Setm', 'sPa9', 'yf6p'
            ])];
        }

        if (!empty($data['PWtf'])) {
            $score4 += [0, 1, 2, 3, 4, 5][array_search($data['PWtf'], [
                'Kohv', 'n6ma', 'wk3a', '5KgK', 'K9Fi', 'Ziav'
            ])];
        }

        if (!empty($data['o8CA'])) {
            $score4 += [0, 1, 2, 3, 4, 5][array_search($data['o8CA'], [
                'CPnL', 'dsv9', 'AJWz', 'm6vB', 'z6B8', '8ySN'
            ])];
        }

        if (!empty($data['wcSw'])) {
            $score4 += [0, 1, 2, 3, 4, 5][array_search($data['wcSw'], [
                'fbxF', 'EcB4', 'Bvaf', 'JSnE', 'tyzh', 'opps'
            ])];
        }

        if (!empty($data['ntcB'])) {
            $score4 += [0, 1, 2, 3, 4, 5][array_search($data['ntcB'], [
                '6kA4', 'jEE6', 'pHtP', 'FbhZ', 'i4Tx', 'XRj4'
            ])];
        }

        if (!empty($data['DqPR'])) {
            $score4 += [0, 1, 2, 3, 4, 5][array_search($data['DqPR'], [
                'fNA2', 'jqpE', 'm2kC', 'YQRB', 'xJh7', '8uio'
            ])];
        }

        $score5 = 0;

        if (!empty($data['N5hM'])) {
            foreach ($data['N5hM'] as $index => $N5hM) {
                if ($N5hM && !empty($data['SgmR'][$index]) && is_numeric($data['SgmR'][$index])) {
                    $score5 += $data['SgmR'][$index] * .1;
                }
            }
        }

        $totalScore = $score1 + $score2 + $score3 + $score4 + $score5;
        $salary = $totalScore * (747300 * .1);

        return [
            [
                'title' => 'Образование',
                'value' => $score1
            ],
            [
                'title' => 'Опыт работы',
                'value' => $score2
            ],
            [
                'title' => 'Язык',
                'value' => $score3
            ],
            [
                'title' => 'Программное обеспечение и оргтехника',
                'value' => $score4
            ],
            [
                'title' => 'Сертификаты',
                'value' => $score5
            ],
            [
                'title' => 'Сумма баллов',
                'value' => $totalScore
            ],
            [
                'title' => 'Расчётная зарплата',
                'value' => number_format($salary, 2)
            ]
        ];
    }
}
