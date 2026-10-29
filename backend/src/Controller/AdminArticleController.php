<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ArticleRepository;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AdminArticleController
{
    #[Route('/api/admin/articles', methods: ['GET'])]

    public function getAllArticles(ArticleRepository $repository): JsonResponse
    {

        $articles = $repository->findAll();

        $data = [];

        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'summary' => $article->getSummary(),
                'content' => $article->getContent(),
                'image_path' => $article->getImagePath(),
                'image_alt' => $article->getImageAlt(),
                'category' => $article->getCategory(),
                'published' => $article->isPublished(),
                'created_at' => $article->getCreatedAt()?->format(DATE_ATOM),
                'updated_at' => $article->getUpdatedAt()?->format(DATE_ATOM),
                'published_at' => $article->getPublishedAt()?->format(DATE_ATOM),
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/api/admin/articles', methods: ['POST'])]
    public function createArticle(
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleRepository $repository,
        SluggerInterface $slugger,
    ): JsonResponse {

        try {
            $data = $request->toArray();
        } catch (JsonException $exception) {
            return new JsonResponse([
                'message' => 'Body-ul trebuie să conțină un JSON valid.',
            ], 400);
        }

        $errors = $this->validateArticleData($data);

        if ($errors !== []) {
            return new JsonResponse([
                'message' => 'Datele articolului nu sunt valide.',
                'errors' => $errors,
            ], 422);
        }

        $baseSlug = $slugger
            ->slug($data['title'])
            ->lower()
            ->truncate(240, '')
            ->toString();

        if ($baseSlug === '') {
            $baseSlug = 'articol';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($repository->findOneBy(['slug' => $slug]) !== null) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setSlug($slug);
        $article->setSummary($data['summary']);
        $article->setContent($data['content']);
        $article->setCategory($data['category']);
        $article->setPublished($data['published'] ?? false);

        $entityManager->persist($article);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'slug' => $article->getSlug(),
            'published' => $article->isPublished(),
        ], 201);
    }


    #[Route('/api/admin/articles/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getArticle(
        int $id,
        ArticleRepository $repository,
    ): JsonResponse {
        $article = $repository->find($id);

        if ($article === null) {
            return new JsonResponse([
                'message' => 'Articolul nu a fost găsit.',
            ], 404);
        }

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'slug' => $article->getSlug(),
            'summary' => $article->getSummary(),
            'content' => $article->getContent(),
            'category' => $article->getCategory(),
            'published' => $article->isPublished(),
            'image_path' => $article->getImagePath(),
            'image_alt' => $article->getImageAlt(),
        ]);
    }


    #[Route('/api/admin/articles/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function editArticle(
        int $id,
        Request $request,
        ArticleRepository $repository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        $article = $repository->find($id);

        if ($article === null) {
            return new JsonResponse([
                'message' => 'Articolul nu a fost găsit.',
            ], 404);
        }

        if (!$this->isCsrfValid(
            $request,
            $csrfTokenManager,
            'admin_article_edit_' . $id,
        )) {
            return new JsonResponse([
                'message' => 'Token CSRF invalid.',
            ], 403);
        }

        try {
            $data = $request->toArray();
        } catch (JsonException $exception) {
            return new JsonResponse([
                'message' => 'Body-ul trebuie să conțină un JSON valid.',
            ], 400);
        }

        $errors = $this->validateArticleData($data, true);

        if ($errors !== []) {
            return new JsonResponse([
                'message' => 'Datele articolului nu sunt valide.',
                'errors' => $errors,
            ], 422);
        }

        if (array_key_exists('title', $data)) {
            $article->setTitle(trim($data['title']));
        }

        if (array_key_exists('summary', $data)) {
            $article->setSummary(trim($data['summary']));
        }

        if (array_key_exists('content', $data)) {
            $article->setContent(trim($data['content']));
        }

        if (array_key_exists('category', $data)) {
            $article->setCategory(trim($data['category']));
        }

        if (array_key_exists('published', $data)) {
            $article->setPublished($data['published']);
        }

        $entityManager->flush();

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'slug' => $article->getSlug(),
            'summary' => $article->getSummary(),
            'content' => $article->getContent(),
            'category' => $article->getCategory(),
            'published' => $article->isPublished(),
            'image_path' => $article->getImagePath(),
            'image_alt' => $article->getImageAlt(),
        ], 200);
    }




    #[Route('/api/admin/articles/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteArticle(
        int $id,
        ArticleRepository $repository,
        CsrfTokenManagerInterface $csrfTokenManager,
        Request $request,
        EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): JsonResponse {

        $article = $repository->find($id);

        if ($article === null) {
            return new JsonResponse([
                'message' => 'Articolul nu a fost găsit.',
            ], 404);
        }

        if (!$this->isCsrfValid(
            $request,
            $csrfTokenManager,
            'admin_article_delete_' . $id,
        )) {
            return new JsonResponse([
                'message' => 'Token CSRF invalid.',
            ], 403);
        }
        $imagePath = $article->getImagePath();

        $entityManager->remove($article);
        $entityManager->flush();

        if (
            $imagePath !== null &&
            preg_match(
                '~^/uploads/articles/[a-f0-9]{32}\.(jpg|png|webp)$~',
                $imagePath
            ) === 1
        ) {
            $filePath = $projectDir
                . '/public/uploads/articles/'
                . basename($imagePath);

            if (is_file($filePath) && !@unlink($filePath)) {
                error_log('Imaginea articolului nu a putut fi ștearsă: ' . $filePath);
            }
        }

        return new JsonResponse(null, 204);
    }



    #[Route('/api/admin/articles/{id}/image', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadArticleImage(
        int $id,
        Request $request,
        ArticleRepository $repository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): JsonResponse {
        $article = $repository->find($id);

        if ($article === null) {
            return new JsonResponse([
                'message' => 'Articolul nu a fost găsit.',
            ], 404);
        }

        if (!$this->isCsrfValid(
            $request,
            $csrfTokenManager,
            'admin_article_image_' . $id,
        )) {
            return new JsonResponse([
                'message' => 'Token CSRF invalid.',
            ], 403);
        }

        $image = $request->files->get('image');

        if (!$image instanceof UploadedFile) {
            return new JsonResponse([
                'message' => 'Selectează o imagine.',
            ], 422);
        }

        if (!$image->isValid()) {
            return new JsonResponse([
                'message' => 'Încărcarea imaginii a eșuat.',
            ], 422);
        }

        // Maximum 5 MB.
        if ($image->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse([
                'message' => 'Imaginea poate avea maximum 5 MB.',
            ], 422);
        }

        // Determinăm tipul din conținutul fișierului.
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $mimeType = $image->getMimeType();

        if (!isset($allowedTypes[$mimeType])) {
            return new JsonResponse([
                'message' => 'Sunt acceptate doar imagini JPG, PNG și WebP.',
            ], 422);
        }

        if (@getimagesize($image->getPathname()) === false) {
            return new JsonResponse([
                'message' => 'Fișierul nu este o imagine validă.',
            ], 422);
        }

        $alt = $request->request->all()['image_alt'] ?? '';

        if (!is_string($alt) || mb_strlen(trim($alt)) > 255) {
            return new JsonResponse([
                'message' => 'Textul alternativ trebuie să fie text de maximum 255 de caractere.',
            ], 422);
        }

        $filename = bin2hex(random_bytes(16))
            . '.'
            . $allowedTypes[$mimeType];

        $directory = $projectDir . '/public/uploads/articles';

        try {
            $image->move($directory, $filename);
        } catch (FileException $exception) {
            return new JsonResponse([
                'message' => 'Imaginea nu a putut fi salvată.',
            ], 500);
        }

        $imagePath = '/uploads/articles/' . $filename;
        $imageAlt = trim($alt);

        $oldImagePath = $article->getImagePath();

        $article->setImagePath($imagePath);
        $article->setImageAlt($imageAlt);

        $entityManager->flush();


        if (
            $oldImagePath !== null &&
            $oldImagePath !== $imagePath &&
            preg_match(
                '~^/uploads/articles/[a-f0-9]{32}\.(jpg|png|webp)$~',
                $oldImagePath
            ) === 1
        ) {
            $oldFile = $directory . '/' . basename($oldImagePath);

            if (is_file($oldFile) && !@unlink($oldFile)) {
                error_log('Imaginea veche nu a putut fi ștearsă: ' . $oldFile);
            }
        }

        return new JsonResponse([
            'image_path' => $imagePath,
            'image_alt' => $imageAlt,
        ]);
    }

    #[Route('/api/admin/csrf/articles/{id}/image', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function articleImageToken(
        int $id,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_article_image_' . $id)
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }









    private function validateArticleData(
        array $data,
        bool $partial = false,
    ): array {
        $errors = [];
        $fields = ['title', 'summary', 'content', 'category'];

        foreach ($fields as $field) {

            if ($partial && !array_key_exists($field, $data)) {
                continue;
            }

            if (
                !isset($data[$field]) ||
                !is_string($data[$field]) ||
                trim($data[$field]) === ''
            ) {
                $errors[$field] = "Câmpul '$field' este obligatoriu și trebuie să fie text.";
                continue;
            }

            $value = trim($data[$field]);

            if ($field === 'title' && mb_strlen($value) > 200) {
                $errors[$field] = 'Titlul poate avea maximum 200 de caractere.';
            }

            if (
                $field === 'category' &&
                !in_array($value, ['Miere', 'Apicultură', 'Sfaturi'], true)
            ) {
                $errors[$field] = 'Categoria selectată nu este validă.';
            }

            if (
                array_key_exists('published', $data) &&
                !is_bool($data['published'])
            ) {
                $errors['published'] = 'Starea de publicare trebuie să fie booleană.';
            }
        }

        return $errors;
    }

    private function isCsrfValid(
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        string $tokenId,
    ): bool {
        $submittedToken = $request->headers->get('X-CSRF-TOKEN', '');

        return $csrfTokenManager->isTokenValid(
            new CsrfToken($tokenId, $submittedToken)
        );
    }
}
