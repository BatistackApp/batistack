<?php

namespace App\Services\Core;

use App\Models\Core\Company;
use App\Models\Core\Plan;
use App\Models\User;
use App\Notifications\Core\WelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyRegistrationService
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Inscrit une nouvelle entreprise et son administrateur.
     *
     * @param array $data Données du formulaire (company_name, user_name, email, password)
     * @return Company
     * @throws \Throwable
     */
    public function register(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            // 1. Créer la compagnie
            $company = Company::create([
                'name' => $data['company_name'],
            ]);

            // 2. Créer l'utilisateur administrateur
            $user = $company->users()->create([
                'name' => $data['user_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_company_admin' => true,
            ]);

            // 3. Souscrire au plan par défaut (ex: essai)
            // On suppose qu'un plan avec le slug 'trial' existe
            $trialPlan = Plan::where('slug', 'trial')->firstOrFail();
            $this->subscriptionService->createSubscription($company, $trialPlan);

            // 4. Envoyer une notification de bienvenue
            $user->notify(new WelcomeNotification());

            return $company;
        });
    }
}
