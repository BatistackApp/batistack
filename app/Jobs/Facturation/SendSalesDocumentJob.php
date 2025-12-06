<?php

namespace App\Jobs\Facturation;

use App\Enums\Facturation\SalesDocumentStatus;
use App\Models\Facturation\SalesDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class SendSalesDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout = 120;

    public function __construct(private readonly SalesDocument $document)
    {
    }

    public function handle(): void
    {
        $html = View::make('documents.pdf.layout', ['document' => $this->document])->render();
        // Nom du fichier temporaire
        $filename = $this->document->reference . '.pdf';
        $tempPath = storage_path('app/temp/' . $filename);

        // Assure-toi que le dossier existe
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        // 2. Génération PDF via Browsershot (Headless Chrome)
        Browsershot::html($html)
            ->newHeadless() // Force une nouvelle instance
            ->noSandbox() // Souvent requis sur Linux/WSL/Docker
            ->setNodeBinary('node') // Optionnel si node est dans le PATH
            ->setNpmBinary('npm')
            ->format('A4')
            ->margins(0, 0, 0, 0) // On gère les marges en CSS (padding)
            ->showBackground() // Important pour voir les background-color gris
            ->save($tempPath);

        // 3. Envoi du Mail
        $recipientEmail = $this->document->tiers->email;

        if ($recipientEmail) {
            Mail::send('emails.sales-document', ['document' => $this->document], function($message) use ($recipientEmail, $tempPath, $filename) {
                $message->to($recipientEmail)
                    ->subject("Votre document : " . $this->document->reference)
                    ->attach($tempPath, [
                        'as' => $filename,
                        'mime' => 'application/pdf',
                    ]);
            });

            // Mise à jour statut
            if ($this->document->status === SalesDocumentStatus::Draft) {
                $this->document->update(['status' => SalesDocumentStatus::Sent]);
            }
        }

        // Nettoyage du fichier temporaire
        @unlink($tempPath);
    }
}
