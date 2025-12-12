<?php

namespace App\Enums\Fleets;

enum FleetType: string
{
    case Car = 'car';
    case Truck = 'truck';
    case HeavyMachinery = 'heavy_machinery';
    case Other = 'other';
}
