<?php

namespace App\Livewire\Space\Auth;

use App\Enums\UserRole;
use App\Jobs\Core\PrepareWorkspaceJob;
use App\Models\Core\Company;
use App\Models\Core\Plan;
use App\Models\User;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Register extends Component implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    public ?array $data = [];
    public $clientSecret; // Pour Stripe

    public function mount(): void
    {
        $this->form->fill();
        // On ne peut pas générer le SetupIntent ici car on n'a pas encore de Company/Customer.
        // On le fera peut-être dynamiquement ou on utilisera une méthode sans SetupIntent préalable (moins sécurisé pour SCA).
        // Pour faire simple et robuste : On crée une Company "Brouillon" ou on utilise l'API Stripe en mode "PaymentMethod" simple côté client.
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // Étape 1 : Choix du Plan
                    Wizard\Step::make('Offre')
                        ->description('Choisissez votre abonnement')
                        ->schema([
                            View::make('livewire.space.auth.steps.plan-selection')
                                ->viewData(['plans' => Plan::where('is_active', true)->where('is_public', true)->get()]),

                            TextInput::make('plan_id')
                                ->required()
                                ->hidden()
                                ->numeric(),
                        ]),

                    // Étape 2 : Informations Entreprise & Admin
                    Wizard\Step::make('Identité')
                        ->description('Vos informations')
                        ->schema([
                            Section::make('Entreprise')
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Raison Sociale')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('siren')
                                        ->label('SIREN')
                                        ->required()
                                        ->length(9)
                                        ->numeric(),
                                    TextInput::make('company_email')
                                        ->label('Email Entreprise')
                                        ->email()
                                        ->required(),
                                ])->columns(2),

                            Section::make('Administrateur')
                                ->schema([
                                    TextInput::make('first_name')
                                        ->label('Prénom')
                                        ->required(),
                                    TextInput::make('last_name')
                                        ->label('Nom')
                                        ->required(),
                                    TextInput::make('email')
                                        ->label('Email Connexion')
                                        ->email()
                                        ->required()
                                        ->unique(User::class, 'email'),
                                    TextInput::make('password')
                                        ->label('Mot de passe')
                                        ->password()
                                        ->required()
                                        ->confirmed(),
                                    TextInput::make('password_confirmation')
                                        ->label('Confirmation')
                                        ->password()
                                        ->required(),
                                ])->columns(2),
                        ]),

                    // Étape 3 : Paiement
                    Wizard\Step::make('Paiement')
                        ->description('Finalisation')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('summary')
                                ->label('Récapitulatif')
                                ->content(fn ($get) => 'Abonnement choisi : ' . (Plan::find($get('plan_id'))?->name ?? 'Aucun')),

                            TextInput::make('card_holder')
                                ->label('Titulaire de la carte')
                                ->required(),

                            // Intégration du formulaire Stripe via une vue partielle
                            // On passe un clientSecret vide pour l'instant, le JS gérera la création de la PaymentMethod sans SetupIntent serveur si besoin,
                            // ou on devra adapter pour créer le Customer avant cette étape.
                            View::make('livewire.space.auth.steps.stripe-payment')
                                ->viewData(['clientSecret' => '']),

                            // Champ caché pour recevoir le PaymentMethod ID de Stripe
                            TextInput::make('payment_method_id')
                                ->hidden()
                                ->required(),
                        ]),
                ])
                // On cache le bouton submit par défaut car c'est le bouton Stripe qui déclenchera l'action
                ->submitAction(new \Illuminate\Support\HtmlString('<div style="display:none"></div>')),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        try {
            DB::transaction(function () use ($data) {
                // 1. Création de l'entreprise
                $company = Company::create([
                    'name' => $data['company_name'],
                    'siren' => $data['siren'],
                    'email' => $data['company_email'],
                    'is_active' => true,
                ]);

                // 2. Création de l'utilisateur Admin
                $user = User::create([
                    'company_id' => $company->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role' => UserRole::ADMINISTRATEUR,
                    'is_company_admin' => true,
                ]);

                // 3. Création de l'abonnement via Cashier (Stripe)
                $plan = Plan::find($data['plan_id']);

                if ($plan->stripe_price_id) {
                    // Création du Customer Stripe et de l'abonnement
                    $company->createOrGetStripeCustomer([
                        'name' => $company->name,
                        'email' => $company->email,
                    ]);

                    $company->newSubscription('default', $plan->stripe_price_id)
                        ->create($data['payment_method_id'], [
                            'email' => $company->email,
                        ]);
                } else {
                    // Fallback si pas de Stripe ID (Plan gratuit ou dev)
                    // On utilise notre ancienne logique ou on lance une erreur
                    // Pour le dev, on peut simuler.
                }

                // 4. Initialisation de l'espace de travail
                PrepareWorkspaceJob::dispatch($company, $user);

                Auth::login($user);
            });

            Notification::make()
                ->title('Bienvenue sur Batistack !')
                ->body('Votre abonnement est actif et votre espace est prêt.')
                ->success()
                ->send();

            $this->redirect(route('filament.admin.pages.dashboard'));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de l\'inscription')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.space.auth.register');
    }
}
