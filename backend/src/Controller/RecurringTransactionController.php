<?php

namespace App\Controller;

use App\DTO\RecurringTransactionInput;
use App\Entity\Category;
use App\Entity\RecurringTransaction;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use App\Repository\CategoryRepository;
use App\Repository\RecurringTransactionRepository;
use App\Security\Voter\AbstractOwnershipVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/recurring-transactions')]
class RecurringTransactionController extends AbstractController
{
    use ResolvesOwnedCategoryTrait;

    #[Route('', name: 'recurring_transaction_list', methods: ['GET'])]
    public function list(RecurringTransactionRepository $recurringTransactionRepository): JsonResponse
    {
        $recurring = $recurringTransactionRepository->findBy(['owner' => $this->getUser()]);

        return $this->json(array_map($this->serialize(...), $recurring));
    }

    #[Route('', name: 'recurring_transaction_create', methods: ['POST'])]
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

        $recurring = new RecurringTransaction();
        $recurring->setOwner($user);
        $this->applyInput($recurring, $input, $category);

        $em->persist($recurring);
        $em->flush();

        return $this->json($this->serialize($recurring), 201);
    }

    #[Route('/{id}', name: 'recurring_transaction_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        RecurringTransactionRepository $recurringTransactionRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $recurring = $recurringTransactionRepository->find($id);
        if (!$recurring) {
            throw new NotFoundHttpException('Transakcja cykliczna nie została znaleziona');
        }

        $this->denyAccessUnlessGranted(AbstractOwnershipVoter::OWNER, $recurring);

        $input = $this->mapInput($request);

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            throw new ValidationFailedException($errors);
        }

        $category = $this->resolveOwnedCategory($categoryRepository, $input->categoryId);

        $this->applyInput($recurring, $input, $category);
        $em->flush();

        return $this->json($this->serialize($recurring));
    }

    #[Route('/{id}', name: 'recurring_transaction_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        RecurringTransactionRepository $recurringTransactionRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $recurring = $recurringTransactionRepository->find($id);
        if (!$recurring) {
            throw new NotFoundHttpException('Transakcja cykliczna nie została znaleziona');
        }

        $this->denyAccessUnlessGranted(AbstractOwnershipVoter::OWNER, $recurring);

        $em->remove($recurring);
        $em->flush();

        return $this->json(null, 204);
    }

    private function mapInput(Request $request): RecurringTransactionInput
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $input = new RecurringTransactionInput();
        $input->categoryId = isset($data['categoryId']) ? (int) $data['categoryId'] : null;
        $input->amount = isset($data['amount']) ? (string) $data['amount'] : null;
        $input->description = $data['description'] ?? null;
        $input->frequency = $data['frequency'] ?? null;
        $input->nextRunDate = $data['nextRunDate'] ?? null;
        $input->active = $data['active'] ?? true;

        return $input;
    }

    private function applyInput(RecurringTransaction $recurring, RecurringTransactionInput $input, Category $category): void
    {
        $recurring->setCategory($category);
        $recurring->setAmount($input->amount);
        $recurring->setDescription($input->description);
        $recurring->setFrequency($input->frequency);
        $recurring->setNextRunDate(new \DateTimeImmutable($input->nextRunDate));
        $recurring->setActive($input->active);
    }

    private function serialize(RecurringTransaction $recurring): array
    {
        return [
            'id' => $recurring->getId(),
            'categoryId' => $recurring->getCategory()->getId(),
            'amount' => $recurring->getAmount(),
            'description' => $recurring->getDescription(),
            'frequency' => $recurring->getFrequency(),
            'nextRunDate' => $recurring->getNextRunDate()->format('Y-m-d'),
            'active' => $recurring->isActive(),
        ];
    }
}
