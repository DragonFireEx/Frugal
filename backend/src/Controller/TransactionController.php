<?php

namespace App\Controller;

use App\DTO\TransactionInput;
use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Repository\TransactionRepository;
use App\Security\Voter\AbstractOwnershipVoter;
use App\Service\BudgetExceededNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transactions')]
class TransactionController extends AbstractController
{
    use ResolvesOwnedCategoryTrait;

    #[Route('', name: 'transaction_list', methods: ['GET'])]
    public function list(Request $request, TransactionRepository $transactionRepository): JsonResponse
    {
        $month = $this->parseMonthFilter($request);

        $categoryId = $request->query->get('categoryId');
        $categoryId = null !== $categoryId ? (int) $categoryId : null;

        /** @var User $user */
        $user = $this->getUser();

        $transactions = $transactionRepository->findFiltered($user, $month, $categoryId);

        return $this->json(array_map($this->serialize(...), $transactions));
    }

    #[Route('/export', name: 'transaction_export', methods: ['GET'])]
    public function export(Request $request, TransactionRepository $transactionRepository): StreamedResponse
    {
        $month = $this->parseMonthFilter($request);

        /** @var User $user */
        $user = $this->getUser();

        $transactions = $transactionRepository->findFiltered($user, $month, null);

        $response = new StreamedResponse(function () use ($transactions): void {
            $handle = fopen('php://output', 'w');
            // BOM so Excel (the main use case for this export) detects UTF-8
            // instead of misrendering Polish diacritics in category/description.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Data', 'Kategoria', 'Typ', 'Kwota', 'Opis'], escape: '\\');
            foreach ($transactions as $transaction) {
                fputcsv($handle, [
                    $transaction->getDate()->format('Y-m-d'),
                    $transaction->getCategory()->getName(),
                    $transaction->getCategory()->getType(),
                    $transaction->getAmount(),
                    $transaction->getDescription() ?? '',
                ], escape: '\\');
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $filename = null !== $month ? sprintf('transakcje-%s.csv', $month) : 'transakcje.csv';
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        ));

        return $response;
    }

    #[Route('', name: 'transaction_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CategoryRepository $categoryRepository,
        TagRepository $tagRepository,
        BudgetExceededNotifier $budgetExceededNotifier
    ): JsonResponse {
        $input = $this->mapInput($request);

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            throw new ValidationFailedException($errors);
        }

        $category = $this->resolveOwnedCategory($categoryRepository, $input->categoryId);

        /** @var User $user */
        $user = $this->getUser();

        $tags = $this->resolveOwnedTags($tagRepository, $user, $input->tagIds);

        $month = substr($input->date, 0, 7);

        $transaction = new Transaction();
        $transaction->setOwner($user);
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $this->applyInput($transaction, $input, $category, $tags);

        $budgetExceededNotifier->checkAndNotify($user, $category, $month, function () use ($em, $transaction): void {
            $em->persist($transaction);
            $em->flush();
        });

        return $this->json($this->serialize($transaction), 201);
    }

    #[Route('/{id}', name: 'transaction_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        TransactionRepository $transactionRepository,
        CategoryRepository $categoryRepository,
        TagRepository $tagRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        BudgetExceededNotifier $budgetExceededNotifier
    ): JsonResponse {
        $transaction = $transactionRepository->find($id);
        if (!$transaction) {
            throw new NotFoundHttpException('Transakcja nie została znaleziona');
        }

        $this->denyAccessUnlessGranted(AbstractOwnershipVoter::OWNER, $transaction);

        $input = $this->mapInput($request);

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            throw new ValidationFailedException($errors);
        }

        $category = $this->resolveOwnedCategory($categoryRepository, $input->categoryId);

        /** @var User $user */
        $user = $this->getUser();

        $tags = $this->resolveOwnedTags($tagRepository, $user, $input->tagIds);

        $month = substr($input->date, 0, 7);

        $budgetExceededNotifier->checkAndNotify($user, $category, $month, function () use ($em, $transaction, $input, $category, $tags): void {
            $this->applyInput($transaction, $input, $category, $tags);
            $em->flush();
        });

        return $this->json($this->serialize($transaction));
    }

    #[Route('/{id}', name: 'transaction_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        TransactionRepository $transactionRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $transaction = $transactionRepository->find($id);
        if (!$transaction) {
            throw new NotFoundHttpException('Transakcja nie została znaleziona');
        }

        $this->denyAccessUnlessGranted(AbstractOwnershipVoter::OWNER, $transaction);

        $em->remove($transaction);
        $em->flush();

        return $this->json(null, 204);
    }

    private function parseMonthFilter(Request $request): ?string
    {
        $month = $request->query->get('month');
        if (null !== $month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new BadRequestHttpException('month musi być w formacie YYYY-MM');
        }

        return $month;
    }

    private function mapInput(Request $request): TransactionInput
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $input = new TransactionInput();
        $input->categoryId = isset($data['categoryId']) ? (int) $data['categoryId'] : null;
        $input->amount = isset($data['amount']) ? (string) $data['amount'] : null;
        $input->description = $data['description'] ?? null;
        $input->date = $data['date'] ?? null;
        $input->tagIds = isset($data['tagIds']) && is_array($data['tagIds'])
            ? array_map(static fn ($id) => (int) $id, $data['tagIds'])
            : [];

        return $input;
    }

    /**
     * @param int[] $tagIds
     *
     * @return Tag[]
     */
    private function resolveOwnedTags(TagRepository $tagRepository, User $owner, array $tagIds): array
    {
        if ([] === $tagIds) {
            return [];
        }

        $tags = $tagRepository->findByIdsForOwner($owner, $tagIds);

        if (count($tags) !== count(array_unique($tagIds))) {
            throw ValidationFailedException::forField('tagIds', 'Jeden lub więcej tagów nie istnieje lub nie należy do użytkownika');
        }

        return $tags;
    }

    /**
     * @param Tag[] $tags
     */
    private function applyInput(Transaction $transaction, TransactionInput $input, Category $category, array $tags): void
    {
        $transaction->setCategory($category);
        $transaction->setAmount($input->amount);
        $transaction->setDescription($input->description);
        $transaction->setDate(new \DateTimeImmutable($input->date));

        foreach ($transaction->getTags()->toArray() as $existingTag) {
            $transaction->removeTag($existingTag);
        }
        foreach ($tags as $tag) {
            $transaction->addTag($tag);
        }
    }

    private function serialize(Transaction $transaction): array
    {
        return [
            'id' => $transaction->getId(),
            'categoryId' => $transaction->getCategory()->getId(),
            'amount' => $transaction->getAmount(),
            'description' => $transaction->getDescription(),
            'date' => $transaction->getDate()->format('Y-m-d'),
            'createdAt' => $transaction->getCreatedAt()->format(DATE_ATOM),
            // array_values() resets keys: ArrayCollection leaves gaps after
            // remove()+add() cycles, which would otherwise serialize this as
            // a JSON object instead of an array.
            'tagIds' => array_values(array_map(static fn (Tag $tag) => $tag->getId(), $transaction->getTags()->toArray())),
        ];
    }
}
