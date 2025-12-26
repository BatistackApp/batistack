<?php

namespace App\Policies\GED;

use App\Models\GED\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tout utilisateur connecté peut voir la liste des documents (le scope global s'occupe du tenant)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        // 1. Vérifier que le document appartient bien à la compagnie de l'utilisateur
        if ($user->company_id !== $document->company_id) {
            return false;
        }

        // 2. Si le document n'est lié à rien (document "global"), on autorise
        if (!$document->documentable) {
            return true;
        }

        // 3. Si le document est lié à une autre entité (Chantier, Employee, etc.),
        //    on délègue la vérification à la Policy de cette entité.
        //    Cela suppose que les autres Policies (ChantierPolicy, etc.) existent.
        //    La méthode Gate::allows vérifie la policy correspondante au modèle.
        return $user->can('view', $document->documentable);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        // On peut modifier un document si on peut voir son parent
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }
}
