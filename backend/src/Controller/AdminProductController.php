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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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


    #[Route('/api/admin/products/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function editProduct(
        int $id,
        ProductRepository $repository,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        CategoryRepository $categoryRepository,
    ): JsonResponse {
        $product = $repository->find($id);

        if ($product === null) {
            return new JsonResponse([
                'message' => 'Produsul nu a fost găsit.',
            ], 404);
        }

        if (!$this->isCsrfValid(
            $request,
            $csrfTokenManager,
            'admin_product_edit_' . $id,
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

        // Validăm câmpurile produsului și variantele trimise.
        $errors = $this->validateProductData($data, true);

        if (array_key_exists('variants', $data)) {
            $errors = array_merge(
                $errors,
                $this->validateVariantsData($data['variants']),
            );
        }

        if ($errors !== []) {
            return new JsonResponse([
                'message' => 'Datele produsului nu sunt valide.',
                'errors' => $errors,
            ], 422);
        }

        // Verificăm categoria înainte de a modifica obiectele.
        $category = $product->getCategory();

        if (array_key_exists('category_id', $data)) {
            $category = $categoryRepository->find($data['category_id']);

            if ($category === null) {
                return new JsonResponse([
                    'message' => 'Categoria selectată nu există.',
                ], 422);
            }
        }

        // Indexăm variantele existente ale acestui produs după ID.
        $existingVariants = [];

        foreach ($product->getVariants() as $variant) {
            $existingVariants[$variant->getId()] = $variant;
        }

        $variantsData = $data['variants'] ?? [];
        $seenIds = [];

        // Verificăm toate ID-urile înainte să aplicăm modificările.
        foreach ($variantsData as $index => $variantData) {
            if (!array_key_exists('id', $variantData)) {
                continue;
            }

            $variantId = $variantData['id'];

            if (!is_int($variantId) || $variantId <= 0) {
                $errors["variants.$index.id"] =
                    'ID-ul variantei trebuie să fie un număr întreg pozitiv.';

                continue;
            }

            if (!isset($existingVariants[$variantId])) {
                $errors["variants.$index.id"] =
                    'Varianta nu există sau nu aparține acestui produs.';

                continue;
            }

            if (isset($seenIds[$variantId])) {
                $errors["variants.$index.id"] =
                    'Aceeași variantă nu poate fi trimisă de două ori.';
            }

            $seenIds[$variantId] = true;
        }

        if ($errors !== []) {
            return new JsonResponse([
                'message' => 'Datele variantelor nu sunt valide.',
                'errors' => $errors,
            ], 422);
        }

        // Actualizăm produsul.
        if (array_key_exists('name', $data)) {
            $product->setName(trim($data['name']));
        }

        if (array_key_exists('summary', $data)) {
            $product->setSummary(trim($data['summary']));
        }

        if (array_key_exists('description', $data)) {
            $product->setDescription(trim($data['description']));
        }

        if (array_key_exists('active', $data)) {
            $product->setActive($data['active']);
        }

        if (array_key_exists('category_id', $data)) {
            $product->setCategory($category);
        }

        // Actualizăm variantele existente sau adăugăm variante noi.
        foreach ($variantsData as $variantData) {
            if (array_key_exists('id', $variantData)) {
                $variant = $existingVariants[$variantData['id']];
            } else {
                $variant = new ProductVariant();
                $variant->setSku('VAR-' . bin2hex(random_bytes(16)));

                $product->addVariant($variant);
                $entityManager->persist($variant);
            }

            $variant->setName(trim($variantData['name']));
            $variant->setPriceInBani($variantData['price_in_bani']);
            $variant->setStockQuantity($variantData['stock_quantity']);

            if (array_key_exists('available', $variantData)) {
                $variant->setAvailable($variantData['available']);
            }
        }

        $entityManager->flush();

        // Construim răspunsul cu toate variantele produsului.
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


    #[Route('/api/admin/products/{id}/image', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadProductImage(
        int $id,
        Request $request,
        ProductRepository $repository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): JsonResponse {
        $product = $repository->find($id);

        if ($product === null) {
            return new JsonResponse([
                'message' => 'Produsul nu a fost găsit.',
            ], 404);
        }

        if (!$this->isCsrfValid(
            $request,
            $csrfTokenManager,
            'admin_product_image_' . $id,
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

        if ($image->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse([
                'message' => 'Imaginea poate avea maximum 5 MB.',
            ], 422);
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $mimeType = $image->getMimeType();

        if (
            !isset($allowedTypes[$mimeType]) ||
            @getimagesize($image->getPathname()) === false
        ) {
            return new JsonResponse([
                'message' => 'Selectează o imagine validă JPG, PNG sau WebP.',
            ], 422);
        }

        $alt = $request->request->all()['image_alt'] ?? '';

        if (!is_string($alt) || mb_strlen(trim($alt)) > 255) {
            return new JsonResponse([
                'message' => 'Descrierea imaginii trebuie să fie text de maximum 255 de caractere.',
            ], 422);
        }

        $filename = bin2hex(random_bytes(16))
            . '.'
            . $allowedTypes[$mimeType];

        $directory = $projectDir . '/public/uploads/products';

        try {
            $image->move($directory, $filename);
        } catch (FileException $exception) {
            return new JsonResponse([
                'message' => 'Imaginea nu a putut fi salvată.',
            ], 500);
        }

        $oldImagePath = $product->getImagePath();
        $imagePath = '/uploads/products/' . $filename;
        $imageAlt = trim($alt);

        $product->setImagePath($imagePath);
        $product->setImageAlt($imageAlt);

        try {
            $entityManager->flush();
        } catch (\Exception $exception) {
            // Dacă salvarea în baza de date eșuează,
            // eliminăm fișierul nou și păstrăm imaginea veche.
            $newFile = $directory . '/' . $filename;

            if (is_file($newFile) && !@unlink($newFile)) {
                error_log(
                    'Imaginea nouă nu a putut fi ștearsă: ' . $newFile
                );
            }

            throw $exception;
        }

        // Imaginea veche se șterge doar după salvarea reușită.
        if (
            $oldImagePath !== null &&
            $oldImagePath !== $imagePath &&
            preg_match(
                '~^/uploads/products/[a-f0-9]{32}\.(jpg|png|webp)$~',
                $oldImagePath
            ) === 1
        ) {
            $oldFile = $directory . '/' . basename($oldImagePath);

            if (is_file($oldFile) && !@unlink($oldFile)) {
                error_log(
                    'Imaginea veche nu a putut fi ștearsă: ' . $oldFile
                );
            }
        }

        return new JsonResponse([
            'image_path' => $imagePath,
            'image_alt' => $imageAlt,
        ]);
    }

    #[Route('/api/admin/products/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteProduct(int $id, ProductRepository $repository, Request $request, CsrfTokenManagerInterface $csrfTokenManager, EntityManagerInterface $entityManager, #[Autowire('%kernel.project_dir%')]
    string $projectDir,): JsonResponse
    {
        $product = $repository->find($id);

        if ($product === null) {
            return new JsonResponse([
                'message' => 'Produsul nu a fost găsit.',
            ], 404);
        }

        if (!$this->isCsrfValid(
            $request,
            $csrfTokenManager,
            'admin_product_delete_' . $id,
        )) {
            return new JsonResponse([
                'message' => 'Token CSRF invalid.',
            ], 403);
        }
        $imagePath = $product->getImagePath();

        foreach ($product->getVariants() as $variant) {
            $entityManager->remove($variant);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        if (
            $imagePath !== null &&
            preg_match(
                '~^/uploads/products/[a-f0-9]{32}\.(jpg|png|webp)$~',
                $imagePath
            ) === 1
        ) {
            $filePath = $projectDir
                . '/public/uploads/products/'
                . basename($imagePath);

            if (is_file($filePath) && !@unlink($filePath)) {
                error_log('Imaginea produsului nu a putut fi ștearsă: ' . $filePath);
            }
        }

        return new JsonResponse(null, 204);
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
