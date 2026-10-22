<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use JsonException;
use App\Entity\Product;
use App\Entity\ProductVariant;

final class AdminProductController extends AbstractController
{
    #[Route('/api/admin/products', methods: ['POST'])]
    public function createProduct(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $repository,
        SluggerInterface $slugger,
        CategoryRepository $categoryRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        // 1. Verificăm tokenul CSRF.
        $submittedToken = $request->headers->get('X-CSRF-TOKEN', '');

        if (!$csrfTokenManager->isTokenValid(
            new CsrfToken('admin_product_create', $submittedToken)
        )) {
            return new JsonResponse([
                'message' => 'Token CSRF invalid.',
            ], 403);
        }

        // 2. Citim JSON-ul.
        try {
            $data = $request->toArray();
        } catch (JsonException $exception) {
            return new JsonResponse([
                'message' => 'Body-ul trebuie să conțină un JSON valid.',
            ], 400);
        }

        // 3. Validăm datele produsului.
        $errors = $this->validateProductData($data);

        // 4. Validăm variantele înainte de a crea obiectele.
        $variantsData = $data['variants'] ?? null;

        $errors = array_merge(
            $this->validateProductData($data),
            $this->validateVariantsData($variantsData),
        );

        if ($errors !== []) {
            return new JsonResponse([
                'message' => 'Datele produsului nu sunt valide.',
                'errors' => $errors,
            ], 422);
        }


        // 5. Verificăm existența categoriei.
        $category = $categoryRepository->find($data['category_id']);

        if ($category === null) {
            return new JsonResponse([
                'message' => 'Categoria selectată nu există.',
            ], 422);
        }

        // 6. Generăm slug-ul.
        $baseSlug = $slugger
            ->slug(trim($data['name']))
            ->lower()
            ->truncate(220, '')
            ->toString();

        if ($baseSlug === '') {
            $baseSlug = 'produs';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($repository->findOneBy(['slug' => $slug]) !== null) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        // 7. Construim produsul.
        $product = new Product();
        $product->setName(trim($data['name']));
        $product->setSlug($slug);
        $product->setSummary(trim($data['summary']));
        $product->setDescription(trim($data['description']));
        $product->setActive($data['active'] ?? false);
        $product->setCategory($category);

        $entityManager->persist($product);

        // 8. Construim variantele și le asociem produsului.
        foreach ($variantsData as $variantData) {
            $variant = new ProductVariant();
            $variant->setName(trim($variantData['name']));
            $variant->setSku('VAR-' . bin2hex(random_bytes(16)));
            $variant->setPriceInBani($variantData['price_in_bani']);
            $variant->setStockQuantity($variantData['stock_quantity']);
            $variant->setAvailable($variantData['available'] ?? true);

            $product->addVariant($variant);

            $entityManager->persist($variant);
        }

        // 9. Salvăm produsul și variantele împreună.
        $entityManager->flush();

        return new JsonResponse([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'active' => $product->isActive(),
        ], 201);
    }

    #[Route('/api/admin/csrf/products/create', methods: ['GET'])]
    public function productCreateToken(
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            [
                'token' => $csrfTokenManager
                    ->getToken('admin_product_create')
                    ->getValue(),
            ],
            200,
            ['Cache-Control' => 'no-store']
        );
    }


    #[Route('/api/admin/products', methods: ['GET'])]
    public function getAllProducts(ProductRepository $repository): JsonResponse
    {
        $products = $repository->findBy([], ['name' => 'ASC']);
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
                'active' => $product->isActive(),
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

    #[Route('/api/admin/products/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProduct(int $id, ProductRepository $repository): JsonResponse
    {
        $product = $repository->find($id);

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



        return new JsonResponse([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'summary' => $product->getSummary(),
            'description' => $product->getDescription(),
            'image_path' => $product->getImagePath(),
            'image_alt' => $product->getImageAlt(),
            'active' => $product->isActive(),
            'created_at' => $product->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $product->getUpdatedAt()?->format(DATE_ATOM),
            'category' => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
            ],
            'variants' => $variants,
        ]);
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


    private function validateProductData(
        array $data,
        bool $partial = false,
    ): array {
        $errors = [];
        $textFields = ['name', 'summary', 'description'];

        foreach ($textFields as $field) {
            if ($partial && !array_key_exists($field, $data)) {
                continue;
            }

            if (
                !isset($data[$field]) ||
                !is_string($data[$field]) ||
                trim($data[$field]) === ''
            ) {
                $errors[$field] =
                    "Câmpul '$field' este obligatoriu și trebuie să fie text.";

                continue;
            }

            if (
                $field === 'name' &&
                mb_strlen(trim($data[$field])) > 200
            ) {
                $errors[$field] =
                    'Numele poate avea maximum 200 de caractere.';
            }
        }

        if (
            array_key_exists('active', $data) &&
            !is_bool($data['active'])
        ) {
            $errors['active'] =
                'Starea produsului trebuie să fie booleană.';
        }

        if (!$partial || array_key_exists('category_id', $data)) {
            if (
                !isset($data['category_id']) ||
                !is_int($data['category_id']) ||
                $data['category_id'] <= 0
            ) {
                $errors['category_id'] =
                    'Categoria trebuie să fie un ID întreg pozitiv.';
            }
        }

        return $errors;
    }

    private function validateVariantsData(mixed $variants): array
    {
        $errors = [];

        if (
            !is_array($variants) ||
            !array_is_list($variants) ||
            $variants === []
        ) {
            return [
                'variants' => 'Produsul trebuie să aibă cel puțin o variantă.',
            ];
        }

        foreach ($variants as $index => $variant) {
            $prefix = 'variants.' . $index;

            if (!is_array($variant)) {
                $errors[$prefix] = 'Varianta trebuie să fie un obiect.';
                continue;
            }

            if (
                !isset($variant['name']) ||
                !is_string($variant['name']) ||
                trim($variant['name']) === '' ||
                mb_strlen(trim($variant['name'])) > 100
            ) {
                $errors[$prefix . '.name'] =
                    'Numele variantei este obligatoriu și poate avea maximum 100 de caractere.';
            }

            if (
                !isset($variant['price_in_bani']) ||
                !is_int($variant['price_in_bani']) ||
                $variant['price_in_bani'] <= 0
            ) {
                $errors[$prefix . '.price_in_bani'] =
                    'Prețul trebuie să fie un număr întreg pozitiv, exprimat în bani.';
            }

            if (!array_key_exists('stock_quantity', $variant)) {
                $errors[$prefix . '.stock_quantity'] =
                    'Trimite cantitatea din stoc sau null pentru stoc neurmărit.';
            } elseif (
                $variant['stock_quantity'] !== null &&
                (
                    !is_int($variant['stock_quantity']) ||
                    $variant['stock_quantity'] < 0
                )
            ) {
                $errors[$prefix . '.stock_quantity'] =
                    'Stocul trebuie să fie un număr întreg pozitiv, zero sau null.';
            }

            if (
                array_key_exists('available', $variant) &&
                !is_bool($variant['available'])
            ) {
                $errors[$prefix . '.available'] =
                    'Disponibilitatea trebuie să fie booleană.';
            }
        }

        return $errors;
    }
}
