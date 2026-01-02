<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-accent mb-5">
        <div class="card-body">
            <div class="flex flex-row">
                <img src="{{ Storage::disk('public')->url('modules/'.$feature->slug.'.png') }}" class="w-[100px] h-[100px] rounded-full me-5" alt="">
                <div class="flex flex-col">
                    <span class="text-2xl font-bold text-gray-800 mb-2">{{ $feature->name }}</span>
                    <div>
                        <div class="badge badge-accent text-white me-3">{{ $feature->type->getLabel() }}</div>
                        @if(!$feature->is_optional)
                            <span class="text-ovh-blue font-medium">Disponible dans un plan par defaut</span>
                        @else
                            <span class="text-ovh-blue font-medium">{{ Number::currency($feature->plans()->first()->price_monthly, 'EUR', 'fr') }} / par mois</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm mb-5">
        <div class="card-body">
            <h3 class="card-title">Description</h3>
            {{ $feature->description }}
        </div>
    </div>
    <div class="flex justify-between">
        <div class="card bg-base-100 w-[45%] shadow-sm mb-5">
            <div class="card-body">
                <h3 class="card-title">Gallerie</h3>

            </div>
        </div>
        <div class="card bg-base-100 w-[45%] shadow-sm mb-5">
            <div class="card-body">
                <h3 class="card-title">Note de version</h3>
                {!! $feature->documentation_html !!}
            </div>
        </div>
    </div>
</div>
