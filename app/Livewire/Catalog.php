<?php

namespace App\Livewire;

use App\Enums\Core\TypeFeature;
use App\Models\Core\Feature;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Catalog extends Component
{
    use WithPagination;

    public ?string $search = '';
    public ?string $type = null;
    public int $perPage = 12;

    public array $selectedFeatureIds = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => null],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingType()
    {
        $this->resetPage();
    }

    public function toggleSelection(int $featureId)
    {
        if (in_array($featureId, $this->selectedFeatureIds)) {
            $this->selectedFeatureIds = array_diff($this->selectedFeatureIds, [$featureId]);
        } else {
            $this->selectedFeatureIds[] = $featureId;
        }
    }

    public function render()
    {
        $features = Feature::query()
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->when($this->type, function (Builder $query) {
                $query->where('type', $this->type);
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.catalog', [
            'features' => $features,
            'types' => TypeFeature::cases(),
        ]);
    }
}
