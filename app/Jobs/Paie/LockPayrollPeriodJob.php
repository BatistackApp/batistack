<?php

namespace App\Jobs\Paie;

use App\Models\Paie\PayrollPeriods;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LockPayrollPeriodJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PayrollPeriods $periods)
    {
    }

    public function handle(): void
    {
        if ($this->periods->status === 'draft') {
            $this->periods->update(['status' => 'locked']);
        }
    }
}
