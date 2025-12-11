<?php

namespace App\Services\Fleets;

use Exception;
use Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Log;

class UlysService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $username;
    protected string $password;
    protected string $jwtToken;
    public function __construct()
    {
        $this->baseUrl = config('ulys.base_url');
        $this->clientId = config('ulys.client_id');
        $this->clientSecret = config('ulys.client_secret');
        $this->username = config('ulys.username');
        $this->password = config('ulys.password');
    }

    /**
     * Tente de s'authentifier auprès de l'API Ulys et stocke le JWT.
     * La durée de vie du jeton est de 1 heure selon la documentation (sandbox).
     *
     * @return bool Vrai si l'authentification a réussi.
     */
    public function authenticate(): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/api/user/authenticate", [
                'username' => $this->username,
                'password' => $this->password,
            ])->throw(); // Lance une exception pour les erreurs 4xx ou 5xx

            $data = $response->json();

            // Le token est typiquement sous data.token dans ce type de réponse
            if (isset($data['token'])) {
                $this->jwtToken = $data['token'];
                Log::info('Authentification Ulys réussie.', ['token_prefix' => substr($this->jwtToken, 0, 10) . '...']);
                return true;
            }

            Log::error('Authentification Ulys échouée: Jeton non reçu.', ['response' => $data]);
            return false;

        } catch (Exception $e) {
            Log::error('Erreur d\'authentification Ulys.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Récupère les consommations (passages) de péage pour une période donnée.
     *
     * @param string $startDate Date de début (Format YYYY-MM-DD)
     * @param string $endDate Date de fin (Format YYYY-MM-DD)
     * @return array La liste des consommations ou un tableau vide en cas d'erreur.
     */
    public function getConsumptions(string $startDate, string $endDate): array
    {
        if (!$this->jwtToken && !$this->authenticate()) {
            return []; // Échec de l'authentification
        }

        try {
            $response = Http::withToken($this->jwtToken)
                ->get("{$this->baseUrl}/api/consumptions/getconsumptions", [
                    'dateFrom' => $startDate,
                    'dateTo' => $endDate,
                ])->throw();

            $data = $response->json();

            // Selon la doc, la liste est sous la clé 'consumptionList'
            return $data['consumptionList'] ?? [];

        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des consommations Ulys.', [
                'error' => $e->getMessage(),
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
            // Tentative de ré-authentification si c'est une erreur 401/403 (jeton expiré)
            if ($e->getCode() === 401 || $e->getCode() === 403) {
                $this->jwtToken = ''; // Force la reconnexion au prochain appel
            }
            return [];
        }
    }

    /**
     * Vide la mémoire Sandbox, nécessaire entre chaque acte de gestion en mode test.
     *
     * @return bool
     */
    public function clearSandboxMemory(): bool
    {
        if (config('ulys.env') !== 'sandbox') {
            return true; // Ne rien faire en production
        }

        if (!$this->jwtToken && !$this->authenticate()) {
            return false;
        }

        try {
            $response = Http::withToken($this->jwtToken)
                ->post("{$this->baseUrl}/api/sandboxmemory/clearsandboxmemory")
                ->throw();

            return $response->successful();
        } catch (Exception $e) {
            Log::warning('Erreur lors de la tentative de vider la mémoire Ulys Sandbox.', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
