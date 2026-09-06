<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TagInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public ?string $name = null;
}
