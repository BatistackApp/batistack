<?php

namespace App\Observers\Comptabilite;

use App\Enums\Comptabilite\AccountClass;
use App\Models\Comptabilite\ComptaAccount;

class ComptaAccountObserver
{
    public function saving(ComptaAccount $comptaAccount): void
    {
        if (empty($comptaAccount->class_code) && !empty($comptaAccount->number)) {
            $firstDigit = (int) substr($comptaAccount->number, 0, 1);
            $comptaAccount->class_code = AccountClass::tryFrom($firstDigit);
        }
    }
}
