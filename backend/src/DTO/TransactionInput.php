<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TransactionInput
{
    #[Assert\NotBlank]
    public ?int $categoryId = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?string $amount = null;

    #[Assert\Length(max: 255)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Date]
    public ?string $date = null;
}
