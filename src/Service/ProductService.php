<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Common\IdDto;
use App\Dto\Product\CreateProductDto;
use App\Dto\Product\UpdateProductDto;
use App\Entity\Product;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductService
{
    public function __construct(
        private ValidatorInterface $validator,
        private ProductRepository $repo,
        private SkuGenerator $skuGen,
        private ManagerRegistry $doctrine,
    ) {}

    public function create(CreateProductDto $dto): Product
    {
        $this->assertValid($dto);

        $id  = class_exists(Uuid::class) ? (string) Uuid::v7() : self::generateGuid();
        $sku = $this->skuGen->generate($dto->name);

        $product = new Product($id, $dto->name, $sku, (float) $dto->price);
        $this->repo->add($product, true);

        return $product;
    }

    public function get(string $id): Product
    {
        $this->assertValidId($id);

        $product = $this->repo->find($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        return $product;
    }

    public function update(string $id, UpdateProductDto $dto): Product
    {
        $this->assertValidId($id);
        $this->assertValid($dto);

        $product = $this->repo->find($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        $dirty = false;
        if ($dto->name !== null && $dto->name !== $product->getName()) {
            $product->setName($dto->name);
            $dirty = true;
        }
        if ($dto->price !== null && (float) $dto->price !== $product->getPrice()) {
            $product->setPrice((float) $dto->price);
            $dirty = true;
        }

        if ($dirty) {
            $this->doctrine->getManager()->flush();
        }

        return $product;
    }

    private function assertValid(object $dto): void
    {
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new ValidationException((string) $errors);
        }
    }

    private function assertValidId(string $id): void
    {
        $idDto = new IdDto();
        $idDto->id = $id;
        $errors = $this->validator->validate($idDto);
        if (count($errors) > 0) {
            throw new NotFoundException('Not Found');
        }
    }

    private static function generateGuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}
