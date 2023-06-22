<?php

namespace App\Jobs;

use App\Lib\Indicators;
use App\Lib\Legacy\Indicators\IndicatorsComputer;
use App\Models\IndicatorValue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class ComputeIndicatorValues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    protected $indicatorValue;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(IndicatorValue $indicatorValue)
    {
        $this->indicatorValue = $indicatorValue;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->indicatorValue->outdated = 2;
        $this->indicatorValue->save();

        $indicators = new IndicatorsComputer;
        $data = $indicators->compute($this->indicatorValue);

        $this->indicatorValue->refresh();
        $this->indicatorValue->data = $data;

        $this->indicatorValue->invalidated_at = $this->indicatorValue->outdated == 1 ?
            $this->indicatorValue->updated_at : null;

        $this->indicatorValue->outdated = $this->indicatorValue->outdated == 1 ? true : false;
        $this->indicatorValue->save();

        Indicators::compute(true);
    }
}
