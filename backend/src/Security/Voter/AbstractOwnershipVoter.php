<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Base voter for the 'OWNER' attribute: grants access only if the subject's
 * owner is the currently logged-in user.
 */
abstract class AbstractOwnershipVoter extends Voter
{
    public const OWNER = 'OWNER';

    /**
     * @return class-string the entity class this voter guards
     */
    abstract protected function supportedClass(): string;

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::OWNER === $attribute && is_a($subject, $this->supportedClass());
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $subject->getOwner() === $user;
    }
}
