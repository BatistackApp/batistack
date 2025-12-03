<?php

namespace App\Console\Commands\Chantiers;

use App\Enums\Chantiers\ChantiersStatus;
use App\Models\Chantiers\Chantiers;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CheckChantiersDelayCommand extends Command
{
    protected $signature = 'chantiers:check-delay';

    protected $description = 'Vérifie les chantiers dont la date de fin prévue est dépassée';

    public function handle(): void
    {
        $today = now()->startOfDay();

        Chantiers::where('is_overdue', true)
            ->where(function (Builder $query) use ($today) {
                $query->where('end_date_planned', '>=', $today)
                    ->orWhere('status', '!=', ChantiersStatus::ONGOING);
            })
            ->update(['is_overdue' => false]);

        $affecteds = Chantiers::where('is_overdue', false)
            ->where('status', ChantiersStatus::ONGOING)
            ->where('end_date_planned', '<=', $today)
            ->get();

        foreach ($affecteds as $affected) {
            $affected->update(['is_overdue' => true]);

            Notification::make()
                ->title("Chantiers en Retard")
                ->body("Chantier {$affected->reference} est en retard !")
                ->warning()
                ->sendToDatabase($affected->company->users);
        }
    }
}
