<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Models\User;
use App\Lib\UserMerger;

class MergeClients implements ToCollection
{
    protected function merge($client, $realName, $mergeClient, $name)
    {
        if (!$client) {
            echo 'Rename ' . $name . "\n";

            $names = explode(' ', $realName);

            $profile = $mergeClient->profile;

            if (isset($profile['basic'])) {
                $profile['basic']['last_name'] = $names[0];
                $profile['basic']['first_name'] = isset($names[1]) ? $names[1] : '';
                $profile['basic']['middle_name'] = isset($names[2]) ? $names[2] : '';
            } else {
                $profile['_']['last_name'] = $names[0];
                $profile['_']['first_name'] = isset($names[1]) ? $names[1] : '';
                $profile['_']['middle_name'] = isset($names[2]) ? $names[2] : '';
            }

            $mergeClient->profile = $profile;
            $mergeClient->timestamps = false;
            $mergeClient->save();
        } else {
            $count = UserMerger::mergeUser($mergeClient, $client);
            echo "$count ({$mergeClient->id}->{$client->id})\n";
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = trim(preg_replace('/\s+/', ' ', $row[0]));

            if (!$name) {
                continue;
            }

            $realName = trim(preg_replace('/\s+/', ' ', $row[1]));

            $mergeClient = User::where('name', $name);

            if (!$mergeClient->count()) {
                echo 'No ' . $name . "\n";
                continue;
            }

            if (!$mergeClient->count() > 1) {
                echo 'Many ' . $name . "\n";
                exit;
            }

            $mergeClient = $mergeClient->first();

            $client = User::where('name', $realName)
                ->where('id', '<>', $mergeClient->id)
                ->first();

            $this->merge($client, $realName, $mergeClient, $name);
        }
    }
}
