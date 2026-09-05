<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationFailedException extends \RuntimeException
{
    public function __construct(private readonly ConstraintViolationListInterface $violations)
    {
        parent::__construct('Validation failed');
    }

    public static function forField(string $field, string $message): self
    {
        return new self(new ConstraintViolationList([
            new ConstraintViolation($message, $message, [], null, $field, null),
        ]));
    }

    /**
     * @return array<string, string>
     */
    public function getViolationsAsArray(): array
    {
        $result = [];
        foreach ($this->violations as $violation) {
            $result[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $result;
    }
}
