<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\StatsCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stats')]
class StatsController extends AbstractController
{
    #[Route('/monthly', name: 'stats_monthly', methods: ['GET'])]
    public function monthly(Request $request, StatsCalculator $statsCalculator): JsonResponse
    {
        $month = $request->query->get('month') ?? (new \DateTimeImmutable())->format('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->json(['error' => 'month musi być w formacie YYYY-MM'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->json($statsCalculator->calculateMonthly($user, $month));
    }
}
