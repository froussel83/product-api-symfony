<?php

declare(strict_types=1);

namespace App\Transformer;

use App\Entity\Product;

final class ProductTransformer
{
    public function toArray(Product $p): array
    {
        return [
            'id'        => $p->getId(),
            'name'      => $p->getName(),
            'sku'       => $p->getSku(),
            'price'     => $p->getPrice(),
            'createdAt' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $p->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
