<?php

namespace App\Trait;

use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot du trait : Applique le Scope Global automatiquement.
     */
    protected static function bootBelongsToCompany(): void
    {
        // 1. Lors de la création, on assigne automatiquement l'ID de la company de l'user connecté
        static::creating(function ($model) {
            if (Auth::check() && ! $model->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });

        // 2. Global Scope : On ne récupère QUE les données de la company de l'user
        // Note: On n'applique pas ce scope si on est en console (artisan) pour éviter des bugs de jobs
        if (Auth::check() && !app()->runningInConsole()) {
            static::addGlobalScope('company', function (Builder $builder) {
                $builder->where('company_id', Auth::user()->company_id);
            });
        }
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
