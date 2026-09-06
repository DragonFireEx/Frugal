<?php

namespace App\Security\Voter;

use App\Entity\RecurringTransaction;

class RecurringTransactionVoter extends AbstractOwnershipVoter
{
    protected function supportedClass(): string
    {
        return RecurringTransaction::class;
    }
}
