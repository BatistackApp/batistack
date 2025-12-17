<?php

namespace App\Console\Commands\GPAO;

use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\GPAO\ProductionOrder;
use App\Models\RH\Team; // Import du modèle Team
use App\Notifications\GPAO\ProductionOrderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckProductionOrderDelaysCommand extends Command
{
    protected $signature = 'gpao:check-delays';
    protected $description = 'Vérifie les ordres de fabrication en cours et alerte en cas de retard.';

    public function handle(): void
    {
        $this->info('Vérification des retards des ordres de fabrication...');

        // Récupérer les OF en retard qui n'ont pas encore été notifiés aujourd'hui
        $productionOrders = ProductionOrder::where('status', ProductionOrderStatus::InProgress)
            ->where('planned_end_date', '<', Carbon::today())
            ->where(function ($query) {
                $query->whereNull('notified_at')
                      ->orWhereDate('notified_at', '<', Carbon::today()); // Notifier une fois par jour
            })
            ->with(['assignedTo'])
            ->get();

        if ($productionOrders->isEmpty()) {
            $this->info('Aucun ordre de fabrication en retard ou déjà notifié aujourd\'hui.');
            return;
        }

        $this->comment("Envoi d'alertes pour les ordres de fabrication en retard...");

        foreach ($productionOrders as $productionOrder) {
            $assignedTo = $productionOrder->assignedTo;

            if ($assignedTo) {
                try {
                    // Si l'assignation est une équipe, notifier tous les membres
                    if ($assignedTo instanceof Team) {
                        foreach ($assignedTo->members as $member) {
                            if (method_exists($member, 'notify')) {
                                $member->notify(new ProductionOrderNotification($productionOrder, 'delayed'));
                            }
                        }
                        $this->info("  Alerte envoyée pour l'OF {$productionOrder->reference} à l'équipe {$assignedTo->name}.");
                    }
                    // Si c'est un employé ou un autre type d'entité notifiable
                    elseif (method_exists($assignedTo, 'notify')) {
                        $assignedTo->notify(new ProductionOrderNotification($productionOrder, 'delayed'));
                        $this->info("  Alerte envoyée pour l'OF {$productionOrder->reference} assigné à {$assignedTo->name}.");
                    }

                    // Marquer comme notifié pour éviter les spams
                    $productionOrder->update(['notified_at' => now()]);

                } catch (\Exception $e) {
                    $this->error("  Erreur lors de l'envoi de l'alerte pour l'OF {$productionOrder->reference}: " . $e->getMessage());
                }
            }
        }

        $this->info('Vérification des retards des ordres de fabrication terminée.');
    }
}
