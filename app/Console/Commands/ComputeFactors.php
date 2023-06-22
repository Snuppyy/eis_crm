<?php

namespace App\Console\Commands;

use App\Lib\DocumentResults;
use App\Models\Document;
use Illuminate\Console\Command;

class ComputeFactors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eis:compute-factors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compute employee factors';

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

        foreach ($documents as $document) {
            $data = $document->data;
            $data['results'] = DocumentResults::compute($document->form_id, $data);
            $document->data = $data;
            $document->save();
        }

        echo "Done.\n";

        return 0;
    }
}
