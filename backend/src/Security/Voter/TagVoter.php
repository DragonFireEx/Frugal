<?php

namespace App\Security\Voter;

use App\Entity\Tag;

class TagVoter extends AbstractOwnershipVoter
{
    protected function supportedClass(): string
    {
        return Tag::class;
    }
}
