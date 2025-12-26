<?php

namespace App\Jobs\Chantiers;

use App\Models\Chantiers\ProjectModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessProjectModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ProjectModel $projectModel
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Début du traitement du modèle 3D #{$this->projectModel->id}");

        // 1. Extraction des métadonnées (Simulation)
        // Dans un cas réel, on utiliserait une librairie pour parser le fichier IFC.
        $metadata = [
            'ifc_version' => 'IFC2X3',
            'author' => 'Architecte Anonyme',
            'project_name' => 'Projet Batistack',
            'extracted_at' => now()->toDateTimeString(),
        ];
        $this->projectModel->update(['metadata' => $metadata]);
        Log::info("Métadonnées extraites pour le modèle 3D #{$this->projectModel->id}");


        // 2. Génération de formats web (Simulation)
        // Dans un cas réel, on appellerait un binaire comme IfcConvert.
        // exec("IfcConvert {$originalPath} {$gltfPath}");
        // Puis on attacherait le nouveau fichier à une autre collection ou en conversion.
        // Ici, on se contente de logguer l'intention.
        Log::info("Tâche de conversion en glTF pour le modèle #{$this->projectModel->id} mise en file d'attente (simulation).");


        Log::info("Fin du traitement du modèle 3D #{$this->projectModel->id}");
    }
}
