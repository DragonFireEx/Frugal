<?php

namespace App\Controller;

use App\DTO\BudgetInput;
use App\Entity\Budget;
use App\Entity\Category;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use App\Repository\BudgetRepository;
use App\Repository\CategoryRepository;
use App\Security\Voter\AbstractOwnershipVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/budgets')]
class BudgetController extends AbstractController
{
    use ResolvesOwnedCategoryTrait;

    #[Route('', name: 'budget_list', methods: ['GET'])]
    public function list(BudgetRepository $budgetRepository): JsonResponse
    {
        $budgets = $budgetRepository->findBy(['owner' => $this->getUser()]);

        return $this->json(array_map($this->serialize(...), $budgets));
    }

    #[Route('', name: 'budget_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CategoryRepository $categoryRepository
    ): JsonResponse {
        $input = $this->mapInput($request);

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            throw new ValidationFailedException($errors);
        }

        $category = $this->resolveOwnedCategory($categoryRepository, $input->categoryId);

        /** @var User $user */
        $user = $this->getUser();

        $budget = new Budget();
        $budget->setOwner($user);
        $this->applyInput($budget, $input, $category);

        $em->persist($budget);
        $em->flush();

        return $this->json($this->serialize($budget), 201);
    }

    #[Route('/{id}', name: 'budget_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        BudgetRepository $budgetRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $budget = $budgetRepository->find($id);
        if (!$budget) {
            throw new NotFoundHttpException('Budżet nie został znaleziony');
        }

        $this->denyAccessUnlessGranted(AbstractOwnershipVoter::OWNER, $budget);

        $input = $this->mapInput($request);

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            throw new ValidationFailedException($errors);
        }

        $category = $this->resolveOwnedCategory($categoryRepository, $input->categoryId);

        $this->applyInput($budget, $input, $category);
        $em->flush();

        return $this->json($this->serialize($budget));
    }

    private function mapInput(Request $request): BudgetInput
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $input = new BudgetInput();
        $input->categoryId = isset($data['categoryId']) ? (int) $data['categoryId'] : null;
        $input->monthlyLimit = isset($data['monthlyLimit']) ? (string) $data['monthlyLimit'] : null;

        return $input;
    }

    private function applyInput(Budget $budget, BudgetInput $input, Category $category): void
    {
        $budget->setCategory($category);
        $budget->setMonthlyLimit($input->monthlyLimit);
    }

    private function serialize(Budget $budget): array
    {
        return [
            'id' => $budget->getId(),
            'categoryId' => $budget->getCategory()->getId(),
            'monthlyLimit' => $budget->getMonthlyLimit(),
        ];
    }
}
