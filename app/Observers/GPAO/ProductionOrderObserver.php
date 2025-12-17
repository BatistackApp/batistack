<?php

namespace App\Observers\GPAO;

use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\GPAO\ProductionOrder;
use App\Models\Articles\InventoryStock;
use App\Models\Articles\Product;
use App\Models\RH\Team;
use App\Notifications\GPAO\ProductionOrderNotification; // Import the notification
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductionOrderObserver
{
    /**
     * Handle the ProductionOrder "created" event.
     */
    public function created(ProductionOrder $productionOrder): void
    {
        // Envoyer une notification lors de la création d'un OF
        $this->sendNotification($productionOrder, 'created');
    }

    /**
     * Handle the ProductionOrder "updated" event.
     */
    public function updated(ProductionOrder $productionOrder): void
    {
        // Si le statut a changé, envoyer une notification de changement de statut
        if ($productionOrder->isDirty('status')) {
            $this->sendNotification($productionOrder, 'status_changed');
        }

        // Si l'assignation a changé, envoyer une notification de mise à jour
        if ($productionOrder->isDirty(['assigned_to_id', 'assigned_to_type'])) {
            $this->sendNotification($productionOrder, 'updated');
        }
    }

    /**
     * Handle the ProductionOrder "saving" event.
     * This event is fired before a model is created or updated.
     */
    public function saving(ProductionOrder $productionOrder): void
    {
        // Si le statut passe à "En cours" et que la date de début réelle n'est pas définie
        if ($productionOrder->isDirty('status') && $productionOrder->status === ProductionOrderStatus::InProgress && !$productionOrder->actual_start_date) {
            $productionOrder->actual_start_date = now();
        }

        // Si le statut passe à "Terminé" et que la date de fin réelle n'est pas définie
        if ($productionOrder->isDirty('status') && $productionOrder->status === ProductionOrderStatus::Completed && !$productionOrder->actual_end_date) {
            $productionOrder->actual_end_date = now();
        }
    }

    /**
     * Handle the ProductionOrder "saved" event.
     * This event is fired after a model is created or updated.
     */
    public function saved(ProductionOrder $productionOrder): void
    {
        // Vérifier si le statut a changé pour "Completed"
        if ($productionOrder->isDirty('status') && $productionOrder->status === ProductionOrderStatus::Completed) {
            try {
                DB::transaction(function () use ($productionOrder) {
                    $productToProduce = $productionOrder->product;
                    $quantityProduced = $productionOrder->quantity;
                    $warehouseId = $productionOrder->warehouse_id;

                    if (!$warehouseId) {
                        throw new Exception("Aucun dépôt n'est spécifié pour l'Ordre de Fabrication {$productionOrder->reference}.");
                    }

                    // 1. Décrémenter les stocks des composants
                    foreach ($productToProduce->children as $component) {
                        $requiredQuantity = $component->pivot->quantity * $quantityProduced;

                        // Trouver le stock du composant dans le dépôt spécifié
                        $stock = InventoryStock::where('product_id', $component->id)
                            ->where('warehouse_id', $warehouseId)
                            ->first();

                        if (!$stock) {
                            throw new Exception("Stock introuvable pour le composant {$component->name} (ID: {$component->id}) dans le dépôt spécifié.");
                        }

                        if ($stock->quantity_on_hand < $requiredQuantity) {
                            throw new Exception("Stock insuffisant pour le composant {$component->name}. Nécessaire: {$requiredQuantity}, Disponible: {$stock->quantity_on_hand}.");
                        }

                        $stock->decrement('quantity_on_hand', $requiredQuantity);
                        Log::info("OF {$productionOrder->reference}: Décrémenté {$requiredQuantity} de {$component->name}. Stock restant: {$stock->quantity_on_hand}.");
                    }

                    // 2. Incrémenter le stock du produit fini
                    $finishedProductStock = InventoryStock::where('product_id', $productToProduce->id)
                        ->where('warehouse_id', $warehouseId)
                        ->first();

                    if (!$finishedProductStock) {
                        // Si pas de stock existant, le créer dans le dépôt spécifié
                        $finishedProductStock = InventoryStock::create([
                            'product_id' => $productToProduce->id,
                            'company_id' => $productionOrder->company_id,
                            'warehouse_id' => $warehouseId,
                            'quantity_on_hand' => 0,
                            'min_stock_level' => 0,
                        ]);
                    }

                    $finishedProductStock->increment('quantity_on_hand', $quantityProduced);
                    Log::info("OF {$productionOrder->reference}: Incrémenté {$quantityProduced} de {$productToProduce->name}. Stock total: {$finishedProductStock->quantity_on_hand}.");
                });

                Log::info("Ordre de Fabrication {$productionOrder->reference} traité avec succès. Stocks mis à jour.");

            } catch (Exception $e) {
                Log::error("Erreur lors du traitement de l'OF {$productionOrder->reference}: " . $e->getMessage());
                // Optionnel : Rejeter la transaction ou marquer l'OF avec un statut d'erreur
                // throw $e; // Rejeter la transaction si l'erreur est critique
            }
        }
    }

    /**
     * Handle the ProductionOrder "deleted" event.
     */
    public function deleted(ProductionOrder $productionOrder): void
    {
        $this->sendNotification($productionOrder, 'deleted');
        // Optionnel : Gérer l'annulation d'un OF terminé (remettre les stocks ?)
        // C'est une logique complexe qui dépend des règles métier (ex: annulation possible si OF récent, sinon OD de correction)
    }

    /**
     * Send a notification to the assigned entity.
     */
    private function sendNotification(ProductionOrder $productionOrder, string $type): void
    {
        $assignedTo = $productionOrder->assignedTo;

        if (!$assignedTo) {
            return;
        }

        // Si l'assignation est une équipe, notifier tous les membres
        if ($assignedTo instanceof Team) {
            foreach ($assignedTo->members as $member) {
                if (method_exists($member, 'notify')) {
                    $member->notify(new ProductionOrderNotification($productionOrder, $type));
                }
            }
        }
        // Si c'est un employé ou un autre type d'entité notifiable
        elseif (method_exists($assignedTo, 'notify')) {
            $assignedTo->notify(new ProductionOrderNotification($productionOrder, $type));
        }
    }
}
