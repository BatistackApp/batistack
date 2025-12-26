<?php

namespace App\Observers\Chantiers;

use App\Jobs\Chantiers\ProcessProjectModelJob;
use App\Models\Chantiers\ProjectModel;

class ProjectModelObserver
{
    /**
     * Handle the ProjectModel "created" event.
     */
    public function created(ProjectModel $projectModel): void
    {
        // On ne déclenche le job que si un fichier a bien été attaché
        if ($projectModel->hasMedia('model_file')) {
            ProcessProjectModelJob::dispatch($projectModel);
        }
    }
}
