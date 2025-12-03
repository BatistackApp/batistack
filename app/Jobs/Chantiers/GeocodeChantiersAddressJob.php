<?php

namespace App\Jobs\Chantiers;

use App\Models\Chantiers\Chantiers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeocodeChantiersAddressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Chantiers $chantiers){}

    public function handle(): void
    {
        $address = $this->chantiers->full_address;

        if (empty($address)) return;

        $response = \Http::withoutVerifying()
            ->get('https://nominatim.openstreetmap.org/search',[
                'format' => 'json',
                'q' => $address,
                'limit' => 1,
            ]);

        if ($response->successful() && !empty($response->json())) {
            $data = $response->json()[0];

            $this->chantiers->updateQuietly([
                'latitude' => $data['lat'],
                'longitude' => $data['lon'],
            ]);
        }
    }
}
