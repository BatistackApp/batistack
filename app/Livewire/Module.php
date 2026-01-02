<?php

namespace App\Livewire;

use App\Models\Core\Feature;
use Livewire\Component;

class Module extends Component
{
    public Feature $feature;

    public function mount(Feature $feature)
    {
        $this->feature = $feature->load('plans');
    }

    public function render()
    {
        return view('livewire.module');
    }
}
