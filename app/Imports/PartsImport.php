<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Models\Part;

class PartsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if(empty($row[0])) {
                continue;
            }

            $part = Part::findOrFail($row[0]);

            $part->description_past = $row[2];
            $part->timestamps = false;
            $part->save();
        }
    }
}
