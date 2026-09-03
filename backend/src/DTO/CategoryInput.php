<?php

namespace App\DTO;

use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [Category::TYPE_INCOME, Category::TYPE_EXPENSE])]
    public ?string $type = null;

    #[Assert\Length(exactly: 7)]
    public ?string $color = null;

    #[Assert\Length(max: 50)]
    public ?string $icon = null;
}
