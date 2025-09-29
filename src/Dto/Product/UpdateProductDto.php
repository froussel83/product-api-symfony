<?php

namespace App\Dto\Product;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProductDto
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Type(type: 'numeric')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    public $price;
}
