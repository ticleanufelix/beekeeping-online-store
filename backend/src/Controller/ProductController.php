<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;

final class ProductController extends AbstractController
{
    #[Route('/api/products', methods: ['GET'])]
    public function getAllProducts(
        ProductRepository $repository,
    ): JsonResponse {
        $products = $repository->findBy(
            ['active' => true],
            ['name' => 'ASC']
        );

        $data = [];

        foreach ($products as $product) {
            $category = $product->getCategory();
            $variants = [];

            foreach ($product->getVariants() as $variant) {
                $variants[] = [
                    'id' => $variant->getId(),
                    'name' => $variant->getName(),
                    'sku' => $variant->getSku(),
                    'price_in_bani' => $variant->getPriceInBani(),
                    'stock_quantity' => $variant->getStockQuantity(),
                    'available' => $variant->isAvailable(),
                ];
            }

            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
                'summary' => $product->getSummary(),
                'description' => $product->getDescription(),
                'image_path' => $product->getImagePath(),
                'image_alt' => $product->getImageAlt(),
                'created_at' => $product->getCreatedAt()?->format(DATE_ATOM),
                'updated_at' => $product->getUpdatedAt()?->format(DATE_ATOM),
                'category' => [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                ],
                'variants' => $variants,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/products/{slug}', methods: ['GET'])]
    public function getProduct(
        ProductRepository $repository,
        string $slug,
    ): JsonResponse {
        $product = $repository->findOneBy([
            'slug' => $slug,
            'active' => true,
        ]);

        if ($product === null) {
            return new JsonResponse([
                'message' => 'Produsul nu a fost găsit.',
            ], 404);
        }

        $category = $product->getCategory();
        $variants = [];

        foreach ($product->getVariants() as $variant) {
            $variants[] = [
                'id' => $variant->getId(),
                'name' => $variant->getName(),
                'sku' => $variant->getSku(),
                'price_in_bani' => $variant->getPriceInBani(),
                'stock_quantity' => $variant->getStockQuantity(),
                'available' => $variant->isAvailable(),
            ];
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'summary' => $product->getSummary(),
            'description' => $product->getDescription(),
            'image_path' => $product->getImagePath(),
            'image_alt' => $product->getImageAlt(),
            'created_at' => $product->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $product->getUpdatedAt()?->format(DATE_ATOM),
            'category' => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
            ],
            'variants' => $variants,
        ];

        return new JsonResponse($data);
    }
}
