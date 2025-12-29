<?php

namespace App\Livewire;

use App\Models\Core\Plan;
use Illuminate\Support\Collection;
use Livewire\Component;

class PricingTable extends Component
{
    public Collection $plans;

    public function mount(): void
    {
        $this->plans = Plan::where('is_public', true)
            ->with('features')
            ->orderBy('sort_order')
            ->get();
    }

    public function render()
    {
        return view('livewire.pricing-table');
    }
}
