<?php

namespace App\DTO;

use App\Entity\RecurringTransaction;
use Symfony\Component\Validator\Constraints as Assert;

class RecurringTransactionInput
{
    #[Assert\NotBlank]
    public ?int $categoryId = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?string $amount = null;

    #[Assert\Length(max: 255)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [RecurringTransaction::FREQUENCY_WEEKLY, RecurringTransaction::FREQUENCY_MONTHLY])]
    public ?string $frequency = null;

    #[Assert\NotBlank]
    #[Assert\Date]
    public ?string $nextRunDate = null;

    public bool $active = true;
}
