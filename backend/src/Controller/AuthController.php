<?php

namespace App\Controller;

use App\DTO\RegisterInput;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $input = new RegisterInput();
        $input->email = $data['email'] ?? null;
        $input->password = $data['password'] ?? null;
        $input->name = $data['name'] ?? null;

        $errors = $validator->validate($input);
        if (count($errors) > 0) {
            throw new ValidationFailedException($errors);
        }

        if ($userRepository->findOneBy(['email' => $input->email])) {
            throw new ConflictHttpException('Użytkownik z tym adresem email już istnieje');
        }

        $user = new User();
        $user->setEmail($input->email);
        $user->setName($input->name);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setPassword($hasher->hashPassword($user, $input->password));

        $em->persist($user);
        $em->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ], 201);
    }

    #[Route('/me', name: 'auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ]);
    }

        #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Ta metoda nigdy się nie wykona, żądanie przechwytuje json_login w security.yaml.');
    }
}