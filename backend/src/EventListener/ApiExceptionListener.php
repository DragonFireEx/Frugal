<?php

namespace App\EventListener;

use App\Exception\ValidationFailedException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Formats every exception reaching an /api/* route as JSON, so API clients
 * never see Symfony's default HTML error page. Runs late (low priority) and
 * backs off if a response already exists, since more specific listeners
 * (JWT auth failures, the security firewall) already produce sensible JSON.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -100)]
class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if ($event->hasResponse() || !str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof ValidationFailedException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Validation failed',
                'violations' => $exception->getViolationsAsArray(),
            ], 400));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse([
                'error' => $exception->getMessage() ?: 'An error occurred',
                'code' => $exception->getStatusCode(),
            ], $exception->getStatusCode()));

            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => 'Internal server error',
            'code' => 500,
        ], 500));
    }
}
