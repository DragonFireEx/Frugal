<?php

namespace App\Security\Voter;

use App\Entity\Category;

class CategoryVoter extends AbstractOwnershipVoter
{
    protected function supportedClass(): string
    {
        return Category::class;
    }
}
