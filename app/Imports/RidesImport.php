<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

use App\Models\Activity;
use Storage;

class RidesImport implements ToCollection
{
    private $date;
    private $activities;
    private $index;

    public function collection(Collection $rows)
    {
        foreach ($rows->slice(3) as $row) {
            if(empty($row[1])) {
                continue;
            }

            if(!empty($row[0])) {
                $date = Date::excelToTimestamp($row[0]);

                if($this->date != $date) {
                    $this->date = $date;

                    $this->index = 0;

                    $this->activities = Activity::where('project_id', 6)
                        ->whereHas('timings', function($query) {
                            $query->where('verified', true)
                                ->where('volunteering', 0)
                                ->whereHas('projectUser', function($query) {
                                    $query->where('part_id', 4);
                                })
                                ->whereDate('began_at', date('Y-m-d', $this->date));
                        })
                        ->join('timings', 'activity_id', '=', 'activities.id')
                        ->select('activities.*')
                        ->orderBy('timings.began_at')
                        ->groupBy('activities.id')
                        ->get();
                }
            }

            if(!isset($this->activities[$this->index])) {
                echo 'More records than activities at ' . date('Y-m-d', $this->date) . "\n";
                continue;
            }

            $activity = $this->activities[$this->index++];

            if(empty($activity->forms)) {
                $forms = $activity->forms ?? [29 => [
                    'DQqq' => trim($row[2]),
                    'iah6' => trim($row[3]),
                    'aLxr' => true,
                    'y3ew' => trim($row[4]),
                    '5FsZ' => (int)$row[6],
                ]];

                if(!empty($forms[29]['file'])) {
                    Storage::delete($forms[29]['file']);
                }

                $filename = 'EndTB_UZB_Квитанция-такси_' . date('m-d-Y', $this->date) . '_' . sprintf('%02d', $row[8]) . '.pdf';

                if(!Storage::exists("/taxi/$filename")) {
                    echo "No file /taxi/$filename\n";
                } else {
                    $forms[29]['file'] = "activities/$activity->id/$filename";

                    if(!Storage::exists($forms[29]['file'])) {
                        Storage::copy("/taxi/$filename", $forms[29]['file']);
                    }
                }

                $activity->forms = $forms;
                $activity->timestamps = false;
                $activity->save();
            }
        }
    }
}
