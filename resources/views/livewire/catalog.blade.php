<div class="container mx-auto px-5 py-5">
    <div class="flex flex-col md:flex-row gap-8">

        {{-- Sidebar for Filters --}}
        <aside class="md:w-1/4 lg:w-1/5">
            <div class="card bg-base-100 sticky shadow-sm">
                <div class="card-body">
                    <div class="card-title">Filtres</div>
                    <div class="mb-2">
                        <label for="search" class="block text-sm/6 font-medium text-gray-900">Recherche</label>
                        <div class="mt-2">
                            <input type="text" name="search" id="search" wire:model.live.debounce="search" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" placeholder="you@example.com">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="type" class="block text-sm/6 font-medium text-gray-900">Types</label>
                        <div class="mt-2">
                            <select id="location" name="location" wire:model.live.debounce="type" class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-1.5 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                @foreach($types as $type)
                                    <option value="{{ $type->value }}">{{ $type->getLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-ovh" wire:click="resetFilter">Réinitialiser le filtre</button>
                </div>
            </div>
        </aside>

        {{-- Main Content Area --}}
        <main class="flex-1">
            {{-- Loading Indicator --}}
            <div wire:loading.flex class="items-center justify-center w-full py-12">
                <div class="text-lg font-semibold text-gray-600 dark:text-gray-300">Chargement...</div>
            </div>

            {{-- Features Grid --}}
            <div wire:loading.remove>
                @if($features->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-lg font-semibold text-gray-500">Aucune fonctionnalité trouvée.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($features as $feature)
                            @php
                                $isSelected = in_array($feature->id, $selectedFeatureIds);
                            @endphp
                            <a
                                wire:click="viewModule({{$feature->id}})"
                                class="relative cursor-pointer rounded-lg border bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-lg dark:bg-gray-800 {{ $isSelected ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-gray-200 dark:border-gray-700' }}">

                                {{-- Selected Checkbox Indicator --}}
                                @if($isSelected)
                                    <div class="absolute top-4 right-4 text-indigo-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                @endif

                                <div class="flex flex-col h-full">
                                    {{-- Feature Type Badge --}}
                                    <span class="inline-block mb-3 px-2 py-1 text-xs font-semibold rounded-full self-start
                                    @switch($feature->type)
                                        @case(App\Enums\Core\TypeFeature::MODULE)
                                            bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @break
                                        @case(App\Enums\Core\TypeFeature::OPTION)
                                            bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @break
                                        @case(App\Enums\Core\TypeFeature::SERVICE)
                                            bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @break
                                        @case(App\Enums\Core\TypeFeature::LIMIT)
                                            bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @break
                                    @endswitch
                                ">
                                    {{ $feature->type->getLabel() }}
                                </span>

                                    {{-- Feature Name --}}
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-2">{{ $feature->name }}</h3>

                                    {{-- Feature Description --}}
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 flex-grow">
                                        {{ Str::limit($feature->description, 120) }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-8">
                        {{ $features->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>
