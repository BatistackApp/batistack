<?php

namespace App\Models\Core;

use App\Enums\Paie\PayrollExportFormat;
use App\Models\User;
use App\Observers\Core\CompanyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'default_intervention_margin' => 'decimal:2',
            'payroll_export_format' => PayrollExportFormat::class,
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    /**
     * Vérifie si l'entreprise a accès à une fonctionnalité via son abonnement.
     *
     * @param string $featureCode Le code de la fonctionnalité (ex: "module_gpao")
     * @return bool
     */
    public function hasFeature(string $featureCode): bool
    {
        $subscription = $this->activeSubscription;

        if (!$subscription) {
            return false;
        }

        // On vérifie si la feature est dans les items de l'abonnement
        return $subscription->items()
            ->whereHasMorph('subscribable', [Feature::class], function ($query) use ($featureCode) {
                $query->where('code', $featureCode);
            })
            ->exists();
    }

    /**
     * Récupère la valeur d'une fonctionnalité (ex: limite) pour l'abonnement actif.
     *
     * @param string $featureCode
     * @param mixed $default
     * @return mixed
     */
    public function getFeatureValue(string $featureCode, mixed $default = null): mixed
    {
        $subscription = $this->activeSubscription;

        if (!$subscription) {
            return $default;
        }

        // On cherche l'item correspondant
        $item = $subscription->items()
            ->whereHasMorph('subscribable', [Feature::class], function ($query) use ($featureCode) {
                $query->where('code', $featureCode);
            })
            ->first();

        // Si l'item existe, on retourne sa valeur (stockée dans quantity ou une colonne spécifique si on l'ajoute)
        // Pour l'instant, supposons que la valeur est stockée dans 'quantity' de SubscriptionItem ou qu'on doit aller chercher la valeur du PlanFeature original si pas surchargé.
        // Mais attendez, SubscriptionItem a 'quantity'.
        // Si c'est une limite globale définie dans le Plan, elle est dans plan_feature.value.
        // Si c'est une option ajoutée à l'abonnement, elle est dans subscription_items.quantity.

        if ($item) {
            return $item->quantity ?? $default;
        }

        // Si pas trouvé dans les items explicites, on regarde dans le plan de base
        $plan = $subscription->plan;
        if ($plan) {
            $feature = $plan->features()->where('code', $featureCode)->first();
            if ($feature && $feature->pivot->value !== null) {
                return $feature->pivot->value;
            }
        }

        return $default;
    }

    /**
     * Vérifie si l'entreprise a atteint son quota pour une fonctionnalité donnée.
     *
     * @param string $featureCode Le code de la feature (ex: 'limit_users')
     * @param int $currentUsage L'utilisation actuelle (ex: nombre d'users actifs)
     * @return bool True si le quota est respecté (usage < limite), False sinon.
     */
    public function checkQuota(string $featureCode, int $currentUsage): bool
    {
        $limit = $this->getFeatureValue($featureCode);

        // Si pas de limite définie (null) ou limite infinie (-1), on autorise
        if ($limit === null || $limit == -1) {
            return true;
        }

        return $currentUsage < (int)$limit;
    }
}
