<?php

namespace App\Console\Commands\GPAO;

use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\GPAO\ProductionOrder;
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

        $productionOrders = ProductionOrder::where('status', ProductionOrderStatus::InProgress)
            ->where('planned_end_date', '<', Carbon::today())
            ->with(['assignedTo'])
            ->get();

        if ($productionOrders->isEmpty()) {
            $this->info('Aucun ordre de fabrication en retard.');
            return;
        }

        $this->comment("Envoi d'alertes pour les ordres de fabrication en retard...");

        foreach ($productionOrders as $productionOrder) {
            $assignedTo = $productionOrder->assignedTo;

            if ($assignedTo) {
                try {
                    if (method_exists($assignedTo, 'notify')) {
                        $assignedTo->notify(new ProductionOrderNotification($productionOrder, 'delayed'));
                    }
                    // TODO: Si assignedTo est une équipe, notifier tous les membres de l'équipe

                    $this->info("  Alerte envoyée pour l'OF {$productionOrder->reference} assigné à {$assignedTo->name}.");
                } catch (\Exception $e) {
                    $this->error("  Erreur lors de l'envoi de l'alerte pour l'OF {$productionOrder->reference}: " . $e->getMessage());
                }
            }
        }

        $this->info('Vérification des retards des ordres de fabrication terminée.');
    }
}
