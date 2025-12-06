<?php

namespace App\Console\Commands\Banque;

use App\Jobs\Banque\SyncBridgeTransactionJob;
use App\Models\Banque\BankAccount;
use Illuminate\Console\Command;

class SyncBankAccountCommand extends Command
{
    protected $signature = 'bank:sync';

    protected $description = 'Synchronise tous les comptes bancaires connectés via BridgeAPI';

    public function handle(): void
    {
        $accounts = BankAccount::whereNotNull('bridge_item_id')->where('is_active', true)->get();

        foreach ($accounts as $account) {
            SyncBridgeTransactionJob::dispatch($account);
        }

        $this->info("Synchronisation lancée pour {$accounts->count()} comptes.");
    }
}
