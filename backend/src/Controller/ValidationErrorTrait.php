<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait ValidationErrorTrait
{
    private function validationErrorResponse(ConstraintViolationListInterface $errors): JsonResponse
    {
        $result = [];
        foreach ($errors as $error) {
            $result[$error->getPropertyPath()] = $error->getMessage();
        }

        return $this->json(['errors' => $result], 400);
    }
}
