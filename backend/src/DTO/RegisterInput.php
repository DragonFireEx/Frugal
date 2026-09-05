<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    public ?string $password = null;

    #[Assert\NotBlank]
    public ?string $name = null;
}
