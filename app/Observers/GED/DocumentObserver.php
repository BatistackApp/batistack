<?php

namespace App\Observers\GED;

use App\Models\GED\Document;

class DocumentObserver
{
    public function saving(Document $document): void
    {
        // Si la date d'expiration est passée, le document n'est plus valide
        if ($document->expiration_date && $document->expiration_date->isPast()) {
            $document->is_valid = false;
        }
    }
}
