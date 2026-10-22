<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController
{
    #[Route('/api/categories', methods: ['GET'])]
    public function getCategories(
        CategoryRepository $repository,
    ): JsonResponse {
        $categories = $repository->findBy([], ['name' => 'ASC']);

        $data = [];

        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
            ];
        }

        return new JsonResponse($data);
    }
}
