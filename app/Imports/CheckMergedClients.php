<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Models\User;
use App\Models\UserActivity;

class CheckMergedClients implements ToCollection
{
    protected $collapseWhitespace;

    public function __construct($collapseWhitespace)
    {
        $this->collapseWhitespace = !!$collapseWhitespace;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if ($id = (int) $row[0]) {
                $name2 = trim($row[2]);

                if (!$name2) {
                    continue;
                }

                $client1 = User::findOrFail($id);
                $client2 = User::where('name', $name2)->first();
            } else {
                $name = $row[0];

                if ($this->collapseWhitespace) {
                    $name = preg_replace('/\s+/', ' ', $name);
                }

                $name = trim($name);

                if (!$name) {
                    continue;
                }

                $client1 = User::where('name', $name)->first();

                if (!$client1) {
                    echo $name . "\n";
                    continue;
                }

                $name = $row[1];

                if ($this->collapseWhitespace) {
                    $name = preg_replace('/\s+/', ' ', $name);
                }

                $name = trim($name);

                if (!$name) {
                    continue;
                }

                $client2 = User::where('name', $name)->first();
            }

            if (!$client2) {
                continue;
            }

            $diff = $client1->projects->pluck('id')
                ->diff($client2->projects->pluck('id'));

            foreach ($diff as $projectId) {
                $items = UserActivity::where('user_id', $client1->id)
                    ->whereHas('activity', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    })
                    ->get();

                foreach ($items as $item) {
                    echo $client1->id . ', ' . $client2->id . ', ' . $item->activity_id . ', ' . $item->part_id . "\n";
                }
            }
        }
    }
}
