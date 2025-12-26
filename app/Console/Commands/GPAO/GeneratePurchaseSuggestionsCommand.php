<?php

namespace App\Console\Commands\GPAO;

use App\Enums\Facturation\PurchaseDocumentStatus;
use App\Models\Articles\Product;
use App\Models\Core\Company;
use App\Models\Facturation\PurchaseDocument;
use App\Services\GPAO\MaterialRequirementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeneratePurchaseSuggestionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gpao:generate-purchase-suggestions {--company=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates material deficits and generates purchase suggestion documents.';

    public function __construct(private MaterialRequirementService $mrpService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting purchase suggestion generation...');

        $companyId = $this->option('company');
        $companies = $companyId ? Company::where('id', $companyId)->get() : Company::all();

        foreach ($companies as $company) {
            $this->processCompany($company);
        }

        $this->info('Purchase suggestion generation finished.');
    }

    private function processCompany(Company $company): void
    {
        $this->line("Processing company: {$company->name}...");

        // 1. Calculer les déficits
        $deficits = $this->mrpService->calculateDeficits($company);

        if ($deficits->isEmpty()) {
            $this->line('No material deficits found. Nothing to do.');
            return;
        }

        $this->line("Found {$deficits->count()} products in deficit.");

        // 2. Récupérer les produits et les regrouper par fournisseur principal
        $products = Product::with('mainSupplier')
            ->whereIn('id', $deficits->keys())
            ->get();

        $productsBySupplier = $products->groupBy('main_supplier_id');

        DB::transaction(function () use ($productsBySupplier, $deficits, $company) {
            // 3. Créer un document d'achat par fournisseur
            foreach ($productsBySupplier as $supplierId => $supplierProducts) {
                if (empty($supplierId)) {
                    foreach ($supplierProducts as $product) {
                        Log::warning("Product #{$product->id} ({$product->name}) has a deficit of {$deficits[$product->id]} but no main supplier is defined.");
                    }
                    continue;
                }

                $this->line("-> Creating purchase suggestion for supplier #{$supplierId}...");

                $purchaseDocument = PurchaseDocument::create([
                    'company_id' => $company->id,
                    'tiers_id' => $supplierId,
                    'status' => PurchaseDocumentStatus::Draft,
                    'date' => now(),
                    'notes' => 'Généré automatiquement par le calcul des besoins (MRP).',
                ]);

                // 4. Ajouter les lignes de produits au document
                foreach ($supplierProducts as $product) {
                    $purchaseDocument->lines()->create([
                        'product_id' => $product->id,
                        'description' => $product->name,
                        'quantity' => $deficits[$product->id], // Quantité en déficit
                        'unit_price' => $product->buying_price,
                        'vat_rate' => $product->vat_rate,
                    ]);
                }

                // 5. Recalculer les totaux du document d'achat
                if (method_exists($purchaseDocument, 'recalculate')) {
                    $purchaseDocument->recalculate();
                }

                $this->info("   Purchase document #{$purchaseDocument->id} created.");
            }
        });
    }
}
