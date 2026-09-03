<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Security\Voter\AbstractOwnershipVoter;
use Symfony\Component\HttpFoundation\JsonResponse;

trait ResolvesOwnedCategoryTrait
{
    private function resolveOwnedCategory(CategoryRepository $categoryRepository, ?int $categoryId): Category|JsonResponse
    {
        $category = null !== $categoryId ? $categoryRepository->find($categoryId) : null;

        if (!$category || !$this->isGranted(AbstractOwnershipVoter::OWNER, $category)) {
            return $this->json(['errors' => ['categoryId' => 'Kategoria nie istnieje lub nie należy do użytkownika']], 400);
        }

        return $category;
    }
}
