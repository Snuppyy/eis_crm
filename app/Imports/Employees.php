<?php

namespace App\Imports;

use Hash;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Lib\Positions;
use App\Models\Location;
use App\Models\User;

class Employees implements ToCollection
{
    protected $project;

    public function __construct($project)
    {
        $this->project = $project;
    }

    public function collection(Collection $rows)
    {
        $location = null;

        foreach ($rows as $row) {
            if (!empty($row[0]) || empty($row[3])) {
                continue;
            }

            try {
                if (!$location || $row[1] && $row[1] != $location->code) {
                    $location = Location::where('code', $row[1])->firstOrFail();
                }

                $email = trim($row[3]);
                $employee = User::where('email', $email)->first();

                $name = trim($row[2]);
                $names = explode(' ', $name);

                if (!$employee) {
                    $employee = User::create([
                        'name' => ' ',
                        'email' => $email,
                        'password' => Hash::make('111111'),
                    ]);
                } else {
                    echo $employee->email . "\n";
                }

                if ($employee->name != $name) {
                    $employee->update([
                        'name' => implode(' ', $names),
                        'roles' => 'employee',
                        'profile' => ['_' => [
                            'last_name' => $names[0],
                            'first_name' => $names[1],
                            'middle_name' => implode(' ', array_slice($names, 2))
                        ]]
                    ]);
                }

                Positions::addPosition($employee, $location->id, $this->project, trim($row[4]));
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }
}
