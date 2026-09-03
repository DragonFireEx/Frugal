<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BudgetInput
{
    #[Assert\NotBlank]
    public ?int $categoryId = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?string $monthlyLimit = null;
}
