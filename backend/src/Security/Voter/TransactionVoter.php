<?php

namespace App\Security\Voter;

use App\Entity\Transaction;

class TransactionVoter extends AbstractOwnershipVoter
{
    protected function supportedClass(): string
    {
        return Transaction::class;
    }
}
