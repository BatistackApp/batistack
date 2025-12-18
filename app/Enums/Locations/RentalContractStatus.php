<?php

namespace App\Enums\Locations;

enum RentalContractStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    const Completed = 'completed';
}
