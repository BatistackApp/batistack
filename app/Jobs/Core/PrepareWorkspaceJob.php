<?php

namespace App\Jobs\Core;

use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareWorkspaceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public User $adminUser
    ) {}

    public function handle(): void
    {
        // 1. Créer le dépôt par défaut
        Warehouse::create([
            'company_id' => $this->company->id,
            'name' => 'Dépôt Principal',
            'address' => 'Adresse de l\'entreprise', // À améliorer
            'is_default' => true,
        ]);

        // 2. Créer des catégories de base (si le modèle Category existe, sinon on passe)
        // Supposons qu'on a un modèle ProductCategory
        // \App\Models\Articles\ProductCategory::create([...]);

        // 3. Envoyer un email de bienvenue
        // Mail::to($this->adminUser)->send(new WelcomeMail($this->company));
    }
}
