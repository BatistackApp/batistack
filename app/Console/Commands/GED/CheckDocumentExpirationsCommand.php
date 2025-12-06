<?php

namespace App\Console\Commands\GED;

use App\Models\GED\Document;
use Illuminate\Console\Command;

class CheckDocumentExpirationsCommand extends Command
{
    protected $signature = 'ged:check-expirations';

    protected $description = 'Vérifie les documents qui expirent bientôt (Assurances, Contrats)';

    public function handle(): void
    {
// On cherche les documents qui expirent dans 30 jours exacts
        $documents = Document::whereDate('expiration_date', now()->addDays(30))
            ->with('company.users') // Optimisation
            ->get();

        foreach ($documents as $doc) {
            // Logique de notification (Simplifiée pour l'exemple)
            // On cible les admins du tenant
            // Notification::send($doc->company->users, new DocumentExpiringNotification($doc));

            $this->info("Document {$doc->name} expire bientôt.");
        }

        // On invalide ceux qui sont périmés aujourd'hui
        Document::whereDate('expiration_date', '<', now())
            ->where('is_valid', true)
            ->update(['is_valid' => false]);
    }
}
