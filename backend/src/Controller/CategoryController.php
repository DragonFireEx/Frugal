<?php

namespace App\Controller;

use App\DTO\CategoryInput;
use App\Entity\Category;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use App\Repository\CategoryRepository;
use App\Security\Voter\AbstractOwnershipVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'category_list', methods: ['GET'])]
    public function list(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findBy(['owner' => $this->getUser()]);

        return $this->json(array_map($this->serialize(...), $categories));
    }

    #[Route('', name: 'category_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $input = $this->mapInput($request);

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            throw new ValidationFailedException($errors);
        }

        /** @var User $user */
        $user = $this->getUser();

        $category = new Category();
        $category->setOwner($user);
        $this->applyInput($category, $input);

        $em->persist($category);
        $em->flush();

        return $this->json($this->serialize($category), 201);
    }

    #[Route('/{id}', name: 'category_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw new NotFoundHttpException('Kategoria nie została znaleziona');
        }

        $this->denyAccessUnlessGranted(AbstractOwnershipVoter::OWNER, $category);

        $input = $this->mapInput($request);

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            throw new ValidationFailedException($errors);
        }

        $this->applyInput($category, $input);
        $em->flush();

        return $this->json($this->serialize($category));
    }

    #[Route('/{id}', name: 'category_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw new NotFoundHttpException('Kategoria nie została znaleziona');
        }

        $this->denyAccessUnlessGranted(AbstractOwnershipVoter::OWNER, $category);

        $em->remove($category);
        $em->flush();

        return $this->json(null, 204);
    }

    private function mapInput(Request $request): CategoryInput
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $input = new CategoryInput();
        $input->name = $data['name'] ?? null;
        $input->type = $data['type'] ?? null;
        $input->color = $data['color'] ?? null;
        $input->icon = $data['icon'] ?? null;

        return $input;
    }

    private function applyInput(Category $category, CategoryInput $input): void
    {
        $category->setName($input->name);
        $category->setType($input->type);
        $category->setColor($input->color ?? '#6366f1');
        $category->setIcon($input->icon);
    }

    private function serialize(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'type' => $category->getType(),
            'color' => $category->getColor(),
            'icon' => $category->getIcon(),
        ];
    }
}
