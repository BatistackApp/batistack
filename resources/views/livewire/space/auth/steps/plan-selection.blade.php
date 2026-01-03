<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @foreach($plans as $plan)
        <div
            x-on:click="$wire.set('data.plan_id', {{ $plan->id }})"
            :class="$wire.data.plan_id == {{ $plan->id }} ? 'border-primary-600 ring-2 ring-primary-600' : 'border-gray-200'"
            class="cursor-pointer rounded-xl border bg-white p-6 shadow-sm hover:shadow-md transition-all">

            <h3 class="text-xl font-bold text-gray-900">{{ $plan->name }}</h3>
            <p class="mt-2 text-gray-500">{{ $plan->description }}</p>

            <div class="mt-4 flex items-baseline text-gray-900">
                <span class="text-3xl font-bold tracking-tight">{{ number_format($plan->price_monthly, 2) }}€</span>
                <span class="ml-1 text-sm font-semibold text-gray-500">/mois</span>
            </div>

            <ul role="list" class="mt-6 space-y-4 text-sm leading-6 text-gray-600">
                @foreach($plan->features as $feature)
                    <li class="flex gap-x-3">
                        <svg class="h-6 w-5 flex-none text-primary-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        {{ $feature->name }}
                        @if($feature->pivot->value)
                            <span class="font-semibold">({{ $feature->pivot->value }})</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
