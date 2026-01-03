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
use Laravel\Cashier\Billable; // Import Cashier

#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    use HasFactory, Billable; // Use Billable trait

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

    // Cashier gère sa propre table 'subscriptions', mais nous avons aussi notre modèle Subscription personnalisé.
    // Pour éviter les conflits, nous devrons soit utiliser le modèle de Cashier, soit adapter le nôtre.
    // Dans une architecture SaaS complexe, il est souvent préférable de laisser Cashier gérer la facturation pure
    // et d'avoir une couche "SaaS" au-dessus.
    // Pour l'instant, je garde la relation existante mais attention aux conflits de noms si Cashier utilise aussi 'subscriptions'.
    // Cashier utilise par défaut le modèle \Laravel\Cashier\Subscription.

    // Je vais renommer notre relation pour éviter l'ambiguïté si nécessaire, ou utiliser le modèle Cashier.
    // Pour simplifier l'intégration Stripe, le mieux est d'utiliser le système de Cashier.

    // public function subscriptions(): HasMany
    // {
    //    return $this->hasMany(Subscription::class);
    // }

    public function activeSubscription(): HasOne
    {
        // On utilise la logique Cashier pour récupérer l'abonnement actif
        // 'default' est le nom par défaut de l'abonnement dans Cashier
        return $this->subscription('default');
    }

    /**
     * Vérifie si l'entreprise a accès à une fonctionnalité via son abonnement.
     *
     * @param string $featureCode Le code de la fonctionnalité (ex: "module_gpao")
     * @return bool
     */
    public function hasFeature(string $featureCode): bool
    {
        // Avec Cashier, on vérifie si l'abonnement est actif ('default')
        if (!$this->subscribed('default')) {
            return false;
        }

        // Ensuite, on doit vérifier si le Plan Stripe associé contient la feature.
        // Cela nécessite de lier le Plan local au Plan Stripe.
        // On récupère le plan local via le stripe_price_id
        $stripePriceId = $this->subscription('default')->stripe_price;
        $localPlan = Plan::where('stripe_price_id', $stripePriceId)->first();

        if (!$localPlan) {
            return false;
        }

        return $localPlan->features()->where('code', $featureCode)->exists();
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
        if (!$this->subscribed('default')) {
            return $default;
        }

        $stripePriceId = $this->subscription('default')->stripe_price;
        $localPlan = Plan::where('stripe_price_id', $stripePriceId)->first();

        if ($localPlan) {
            $feature = $localPlan->features()->where('code', $featureCode)->first();
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
