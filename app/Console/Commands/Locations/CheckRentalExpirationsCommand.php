<?php

namespace App\Console\Commands\Locations;

use App\Enums\Locations\RentalContractStatus;
use App\Models\Locations\RentalContract;
use App\Models\User;
use App\Notifications\Locations\RentalContractExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckRentalExpirationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:check-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les contrats de location arrivant à échéance et notifie les responsables.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Vérification des expirations de contrats de location...');

        // Trouver les contrats actifs qui expirent dans les 3 prochains jours
        $expiringContracts = RentalContract::where('status', RentalContractStatus::Active)
            ->whereDate('end_date', '<=', Carbon::now()->addDays(3))
            ->whereDate('end_date', '>=', Carbon::now())
            ->get();

        foreach ($expiringContracts as $contract) {
            // Logique simplifiée : notifier les admins ou le gestionnaire de chantier
            // Ici, on notifie tous les admins de la compagnie du contrat
            $admins = User::where('company_id', $contract->company_id)
                // ->role('admin') // Si Spatie Permission est utilisé
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new RentalContractExpiringNotification($contract));
            }

            $this->info("Notification envoyée pour le contrat #{$contract->id}");
        }

        $this->info('Vérification terminée.');
    }
}
