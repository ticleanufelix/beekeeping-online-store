<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ArticleRepository;

class ArticleController
{
    #[Route('/api/articles', methods: ['GET'])]
    public function getPublishedArticles( ArticleRepository $repository): JsonResponse
    {
       $articles = $repository->findBy(['published' => true]);

       $data = [];

       foreach ($articles as $article) {
          $data[]= [
            'id' => $article -> getId(),
            'title' => $article -> getTitle(),
            'slug' => $article -> getSlug(),
            'summary' => $article->getSummary(),
            'content' => $article -> getContent(),
            'image_path' => $article ->getImagePath(),
            'image_alt' => $article -> getImageAlt(),
            'category' => $article -> getCategory(),
            'created_at' => $article->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $article->getUpdatedAt()?->format(DATE_ATOM),
            'published_at' => $article->getPublishedAt()?->format(DATE_ATOM),
          ];
       }

        return new JsonResponse($data);
    }

    #[Route('/api/articles/{slug}', methods: ['GET'])]

    public function getArticleBySlug (string $slug , ArticleRepository $repository): JsonResponse {

      $article = $repository-> findOneBy([
        'slug' => $slug,
        'published' => true
      ]);
      if($article) {
              $data= [
            'id' => $article -> getId(),
            'title' => $article -> getTitle(),
            'slug' => $article -> getSlug(),
            'summary' => $article->getSummary(),
            'content' => $article -> getContent(),
            'image_path' => $article ->getImagePath(),
            'image_alt' => $article -> getImageAlt(),
            'category' => $article -> getCategory(),
            'created_at' => $article->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $article->getUpdatedAt()?->format(DATE_ATOM),
            'published_at' => $article->getPublishedAt()?->format(DATE_ATOM),
          ];
           return new JsonResponse($data);
      }
      else {
        return new JsonResponse(
    ['message' => 'Articolul nu a fost găsit'],
    404
);
      }
   
    }

   
}