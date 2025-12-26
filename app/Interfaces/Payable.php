<?php

namespace App\Interfaces;

interface Payable
{
    /**
     * Détermine si un paiement associé à ce document est un encaissement.
     *
     * @return bool
     */
    public function isIncomingPayment(): bool;
}
