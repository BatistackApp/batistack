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
}
