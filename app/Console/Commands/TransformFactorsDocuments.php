<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;

class TransformFactorsDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:transform-factors-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transform employee factors documents';

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
        $documents = Document::where('form_id', 55)->with('users')->get();

        $usersWithIncompleteDocuments = collect();

        foreach ($documents as $document) {
            $data = $document->data;

            if (!empty($data['ypci'])) {
                unset($data['ypci']);
            }

            if (!empty($data['wiZD'])) {
                unset($data['wiZD']);
            }

            if (!empty($data['jEfE']) && $data['jEfE'] == '4aqA') {
                unset($data['jEfE']);
                $data['Wuv7'] = true;
                $usersWithIncompleteDocuments->push($document->users[0]);
            }

            if (!empty($data['TW5f']) && is_array($data['TW5f'])) {
                $data['PWku'] = [];

                foreach ($data['TW5f'] as $index => $value) {
                    if ($value == '5FPe') {
                        $data['TW5f'][$index] = null;
                        $data['PWku'][] = true;
                        $usersWithIncompleteDocuments->push($document->users[0]);
                    } else {
                        $data['PWku'][] = null;
                    }
                }
            }

            if (!empty($data['MDPu']) && $data['MDPu'] == '8M9Z') {
                unset($data['MDPu']);
                $data['FzKs'] = true;
                $usersWithIncompleteDocuments->push($document->users[0]);
            }

            $document->data = $data;
            $document->save();
        }

        echo $usersWithIncompleteDocuments->pluck('name')->unique()->sort()->join("\n");

        echo "\n\nDone.\n";

        return 0;
    }
}
