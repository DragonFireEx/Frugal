<?php

namespace App\Controller;

use App\DTO\TagInput;
use App\Entity\Tag;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use App\Repository\TagRepository;
use App\Security\Voter\AbstractOwnershipVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tags')]
class TagController extends AbstractController
{
    #[Route('', name: 'tag_list', methods: ['GET'])]
    public function list(TagRepository $tagRepository): JsonResponse
    {
        $tags = $tagRepository->findBy(['owner' => $this->getUser()], ['name' => 'ASC']);

        return $this->json(array_map($this->serialize(...), $tags));
    }

    #[Route('', name: 'tag_create', methods: ['POST'])]
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

        $tag = new Tag();
        $tag->setOwner($user);
        $tag->setName($input->name);

        $em->persist($tag);
        $em->flush();

        return $this->json($this->serialize($tag), 201);
    }

    #[Route('/{id}', name: 'tag_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        TagRepository $tagRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $tag = $tagRepository->find($id);
        if (!$tag) {
            throw new NotFoundHttpException('Tag nie został znaleziony');
        }

        $this->denyAccessUnlessGranted(AbstractOwnershipVoter::OWNER, $tag);

        $em->remove($tag);
        $em->flush();

        return $this->json(null, 204);
    }

    private function mapInput(Request $request): TagInput
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $input = new TagInput();
        $input->name = $data['name'] ?? null;

        return $input;
    }

    private function serialize(Tag $tag): array
    {
        return [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
        ];
    }
}
