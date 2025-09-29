<?php

namespace App\Dto\Product;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProductDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotNull]
    #[Assert\Type(type: 'numeric')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    public $price;
}
