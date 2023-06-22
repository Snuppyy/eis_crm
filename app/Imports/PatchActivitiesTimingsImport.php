<?php

namespace App\Imports;

use App\Models\Timing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PatchActivitiesTimingsImport extends StringValueBinder implements
    ToCollection,
    WithEvents,
    WithCustomValueBinder,
    WithMapping
{
    protected $employee;

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->employee = User::where('name', 'like', $event->sheet->getDelegate()->getTitle() . '%')
                    ->firstOrFail();
            }
        ];
    }

    public function map($row): array
    {
        return [
            0 => $row[0],
            1 => $row[1],
            2 => $row[2],
            3 => $row[5],
            4 => $row[6],
            5 => $row[8],
            6 => $row[9]
        ];
    }

    public function collection(Collection $rows)
    {
        $updated = 0;

        foreach ($rows->slice(1) as $index => $row) {
            $date = (new Carbon(Date::excelToDateTimeObject($row[0])))->toDateString();
            $start = (new Carbon(Date::excelToDateTimeObject($row[1])))->toTimeString();
            $end = (new Carbon(Date::excelToDateTimeObject($row[2])))->toTimeString();

            $title = $row[3];
            $titleUpdate = $row[4];
            $comment = $row[5];
            $commentUpdate = $row[6];

            $timingQuery = Timing::where('began_at', "$date $start")
                ->where('ended_at', "$date $end")
                ->whereHas('projectUser', function ($query) {
                    $query->where('user_id', $this->employee->id);
                });

            if ($comment) {
                $timingQuery->whereRaw('REGEXP_REPLACE(comment, "[[:space:]]+", " ") = ?', [$comment]);
            }

            if ($title) {
                $timingQuery->whereHas('activity', function ($query) use ($title) {
                    $query->whereRaw('REGEXP_REPLACE(title, "[[:space:]]+", " ") = ?', [$title]);
                });
            }

            $timing = $timingQuery->first();

            if ($timing) {
                $updated++;

                if ($titleUpdate) {
                    $timing->activity->title = $titleUpdate;
                    $timing->activity->save();
                }

                if ($commentUpdate) {
                    $timing->comment = $commentUpdate;
                    $timing->save();
                }
            } else {
                $rowNumber = $index + 1;
                echo "{$this->employee->name} {$rowNumber}\n";
            }
        }

        echo "{$updated} updated\n";
    }
}
