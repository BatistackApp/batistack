<?php

namespace App\Console\Commands\NoteFrais;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class RemindExpenseSubmissionCommand extends Command
{
    protected $signature = 'expenses:remind';

    protected $description = 'Rappel aux employés de soumettre leurs notes de frais';

    public function handle(): void
    {
        // On envoie une notification générale à tous les employés ayant un accès User
        $users = User::whereHas('employee', function($q) {
            $q->where('is_active', true);
        })->get();

        foreach ($users as $user) {
            Notification::make()
                ->title('Fin de mois proche')
                ->body("N'oubliez pas de soumettre vos notes de frais avant le 30 !")
                ->info()
                ->sendToDatabase($user);
        }

        $this->info("Rappel envoyé à {$users->count()} utilisateurs.");
    }
}
