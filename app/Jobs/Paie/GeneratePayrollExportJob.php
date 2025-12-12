<?php

namespace App\Jobs\Paie;

use App\Models\Paie\PayrollSlip;
use App\Services\Paie\PayrollCalculator;
use App\Services\Paie\PayrollExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeneratePayrollExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public PayrollSlip $slip)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PayrollCalculator $calculator, PayrollExportService $exportService): void
    {
        try {
            // 1. Calculer (ou recalculer) le bulletin de paie
            $calculator->calculate($this->slip);

            // 2. Générer le contenu du fichier CSV
            $csvContent = $exportService->generateCsv($this->slip);
            $fileName = $exportService->generateFileName($this->slip);

            // 3. Attacher le fichier CSV au bulletin de paie en utilisant Spatie Media Library
            $this->slip->addMediaFromString($csvContent)
                ->usingFileName($fileName)
                ->toMediaCollection('payroll-exports');

            // Optionnel : Mettre à jour un statut sur le bulletin
            $this->slip->update(['processed_at' => now()]);

            Log::info("Export de paie généré avec succès pour le bulletin #{$this->slip->id}");

        } catch (\Throwable $e) {
            Log::error("Erreur lors de la génération de l'export de paie pour le bulletin #{$this->slip->id}: " . $e->getMessage());
            // Gérer l'échec du job
            $this->fail($e);
        }
    }
}
