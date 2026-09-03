<?php

namespace App\Security\Voter;

use App\Entity\Budget;

class BudgetVoter extends AbstractOwnershipVoter
{
    protected function supportedClass(): string
    {
        return Budget::class;
    }
}
