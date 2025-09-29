<?php

namespace App\Controller\Api;

use App\Dto\Product\CreateProductDto;
use App\Dto\Product\UpdateProductDto;
use App\Service\ProductService;
use App\Transformer\ProductTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductService $products,
        private ProductTransformer $transformer,
    ) {}

    #[Route('', name: 'api_products_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);

        $dto = new CreateProductDto();
        $dto->name  = $payload['name']  ?? null;
        $dto->price = $payload['price'] ?? null;

        $product = $this->products->create($dto);

        return $this->json($this->transformer->toArray($product), 201);
    }

    #[Route('/{id}', name: 'api_products_get', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $product = $this->products->get($id);
        return $this->json($this->transformer->toArray($product), 200);
    }

    #[Route('/{id}', name: 'api_products_put', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);

        $dto = new UpdateProductDto();
        $dto->name  = $payload['name']  ?? null;
        $dto->price = array_key_exists('price', $payload) ? $payload['price'] : null;

        $product = $this->products->update($id, $dto);

        return $this->json($this->transformer->toArray($product), 200);
    }

    /** Decode JSON with exceptions converted by the ApiExceptionSubscriber. */
    private function decodeJson(Request $request): array
    {
        $raw = $request->getContent() ?: '{}';

        // Will throw \JsonException on invalid JSON
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }
}
