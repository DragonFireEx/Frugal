<?php

namespace App\Controller;

use App\Entity\Category;
use App\Exception\ValidationFailedException;
use App\Repository\CategoryRepository;
use App\Security\Voter\AbstractOwnershipVoter;

trait ResolvesOwnedCategoryTrait
{
    private function resolveOwnedCategory(CategoryRepository $categoryRepository, ?int $categoryId): Category
    {
        $category = null !== $categoryId ? $categoryRepository->find($categoryId) : null;

        if (!$category || !$this->isGranted(AbstractOwnershipVoter::OWNER, $category)) {
            throw ValidationFailedException::forField('categoryId', 'Kategoria nie istnieje lub nie należy do użytkownika');
        }

        return $category;
    }
}
