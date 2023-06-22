<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Storage;

class ExportActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:export-services {period-start?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export v3 activities';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($start = strtotime($this->argument('period-start'))) {
            $start = date('Y-m-d H:i:s', $start);
        }

        $db = DB::connection('v3');

        $activities = $db->table('activities', 'ac')
            ->join('assignments as ass', 'ass.id', '=', 'ac.assignment')
            ->select(
                'user',
                'ac.date',
                'ac.start',
                'ac.end',
                'ac.comment',
                'ass.id',
                'administrants',
                'mark',
                'text'
            )
            ->where('project', 9)
            // ->where('ac.status', 1)
            ->orderBy('date')
            ->orderBy('start');

        if ($start) {
            $activities->where('ac.created_at', '>=', $start);
        }

        $excl = [
            248,
            323,
            402,
            418,
            85,
            321,
            5,
            242,
            6,
            329,
            417,
            322,
            427,
            419,
            324,
            404,
            326,
            416,
            10,
            12,
            328,
            15,
            243,
            433,
            330,
            435,
            13,
            457,
            458,
            441,
            434,
            407,
            422,
            475,
            477,
            14,
            247,
            244,
            246,
            436,
            403,
            476,
            400,
            428,
            325,
            414,
            413,
            425,
            327,
            470,
            8,
            'undefined',
            456,
            7,
            468,
            11,
            454,
            474,
        ];

        $subst = [
            '15344' => 'undefined',
            '15384' => 'undefined',
            '18866' => 'undefined',
            '18864' => 'undefined',
            '18928' => 'undefined',
            '20544' => 'undefined',
            '20543' => 'undefined',
            '20298' => 466,
            '25168' => 'undefined',
            '24224' => 'undefined',
            '21532' => 'undefined',
            '23546' => 'undefined',
            '23547' => 'undefined',
            '23557' => 'undefined',
            '23789' => 466,
        ];

        $services = [
            448 => 'Индивидуальная консультация по социальным вопросам',
            446 => [
                'Индивидуальная консультация по социальным вопросам',
                'Скрининг-анкетирование'
            ],
            466 => 'Индивидуальная консультация по юридическим вопросам',
            452 => 'Перенаправление',
            467 => 'Групповая консультация по юридическим вопросам',
            447 => [
                'Индивидуальная консультация по социальным вопросам',
                'Скрининг-анкетирование'
            ],
            471 => 'Индивидуальная консультация по психологическим вопросам',
            449 => 'Групповая консультация по социальным вопросам',
            451 => 'Индивидуальная консультация по социальным вопросам',
            473 => [
                'Индивидуальная консультация по социальным вопросам',
                'Скрининг-анкетирование'
            ]
        ];

        $positions = [];

        $types = [];
        $broken = [];
        $result = [];

        foreach ($activities->get() as $row) {
            $administrants = json_decode($row->administrants, true);

            if (isset($administrants['undefined'])) {
                if (isset($subst[$row->id])) {
                    $user = $db->table('users')
                        ->where('id', $row->user)
                        ->first();

                    $administrants[json_decode($user->position)[0]] = $subst[$row->id];
                } else {
                    $broken[] = [
                        $row->id,
                        $row->comment,
                        $row->mark,
                        $row->text
                    ];
                }
            }

            foreach ($administrants as $position => $responsibility) {
                if (!in_array($responsibility, $excl)) {
                    if (isset($services[$responsibility])) {
                        if(!isset($positions[$position])) {
                            $user = $db->table('users')
                                ->where('position', 'like', "%\"$position\"%")
                                ->first();

                            $positions[$position] = $user->name_ru;
                        }

                        $name = $positions[$position];

                        if(!isset($result[$name])) {
                            $result[$name] = [[
                                'ID',
                                'Дата',
                                'Время',
                                'Сотрудник',
                                'Метка',
                                'Описание',
                                'Комментарий',
                                'Услуга',
                                'Клиент',
                                'Поручение',
                                'Комментарий'
                            ]];
                        }

                        $service = $services[$responsibility];
                        $comment = null;

                        if(is_array($service)) {
                            $comment = $service[1];
                            $service = $service[0];
                        }

                        $result[$name][] = [
                            $row->id,
                            $row->date,
                            "{$row->start}-{$row->end}",
                            $name,
                            '"' . str_replace('"', '""', $row->mark) . '"',
                            '"' . str_replace('"', '""', $row->text) . '"',
                            '"' . str_replace('"', '""', $row->comment) . '"',
                            '"' . $service . '"',
                            null,
                            $comment
                        ];
                    }

                    if (!isset($types[$responsibility])) {
                        $types[$responsibility] = [
                            $responsibility,
                            $db->table('responsibilities')
                                ->select('name_ru')
                                ->where('id', $responsibility)
                                ->value('name_ru'),
                            $row->comment,
                            $row->mark,
                            $row->text
                        ];
                    }
                }
            }
        }

        foreach($types as $type) {
            echo implode("\n", $type) . "\n\n";
        }

        echo "============================================\n\n";

        foreach($broken as $item) {
            echo implode("\n", $item) . "\n\n";
        }

        foreach ($result as $name => $data) {
            $csv = '';

            foreach ($data as $item) {
                $csv .= implode(",", $item) . "\n";
            }

            Storage::put('import/' . $name . '.csv', $csv);
        }

        return 0;
    }
}
