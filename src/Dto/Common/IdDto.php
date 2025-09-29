<?php

namespace App\Dto\Common;

use Symfony\Component\Validator\Constraints as Assert;

class IdDto
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public ?string $id = null;
}
