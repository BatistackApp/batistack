<?php

namespace App\Observers\Core;

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Core\Company;
use Illuminate\Support\Facades\Config;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     *
     * @param  \App\Models\Core\Company  $company
     * @return void
     */
    public function created(Company $company): void
    {
        // Créer le plan comptable par défaut pour la nouvelle entreprise
        $this->createDefaultAccountsForCompany($company);
    }

    /**
     * Crée un plan comptable de base pour une nouvelle entreprise.
     *
     * @param Company $company
     */
    private function createDefaultAccountsForCompany(Company $company): void
    {
        $defaultAccounts = Config::get('compta.default_accounts', []);

        foreach ($defaultAccounts as $accountData) {
            ComptaAccount::create(array_merge($accountData, ['company_id' => $company->id]));
        }
    }
}
