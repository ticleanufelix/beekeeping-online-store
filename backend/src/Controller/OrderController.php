<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductVariantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class OrderController
{
    private const CSRF_TOKEN_ID = 'checkout_order_create';

    #[Route('/api/csrf/orders/create', methods: ['GET'])]
    public function orderCreateToken(
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        return new JsonResponse(
            ['token' => $csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue()],
            200,
            ['Cache-Control' => 'no-store'],
        );
    }

    #[Route('/api/orders', methods: ['POST'])]
    public function createOrder(
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        ProductVariantRepository $variantRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // 1. Verificăm tokenul CSRF.
        $token = new CsrfToken(
            self::CSRF_TOKEN_ID,
            $request->headers->get('X-CSRF-TOKEN', ''),
        );

        if (!$csrfTokenManager->isTokenValid($token)) {
            return new JsonResponse(['message' => 'Token CSRF invalid.'], 403);
        }

        // 2. Citim corpul cererii.
        try {
            $data = $request->toArray();
        } catch (JsonException) {
            return new JsonResponse([
                'message' => 'Corpul cererii trebuie să conțină un JSON valid.',
            ], 400);
        }

        // 3. Validăm câmpurile și structura coșului.
        $errors = $this->validateOrderData($data);

        if ($errors !== []) {
            return new JsonResponse([
                'message' => 'Datele comenzii nu sunt valide.',
                'errors' => $errors,
            ], 422);
        }

        // 4. Citim variantele reale și calculăm totalul în bani.
        $totalInBani = 0;
        $items = [];

        foreach ($data['items'] as $index => $row) {
            $variant = $variantRepository->find($row['variantId']);
            $product = $variant?->getProduct();

            if (
                $variant === null ||
                $product === null ||
                !$product->isActive() ||
                !$variant->isAvailable()
            ) {
                return new JsonResponse([
                    'message' => 'O variantă din coș nu mai poate fi comandată.',
                    'errors' => [
                        "items.$index.variantId" => 'Alege o variantă disponibilă.',
                    ],
                ], 422);
            }

            $unitPrice = $variant->getPriceInBani();
            $quantity = $row['quantity'];

            // Coloana total_in_bani este un INT semnat.
            $remaining = 2_147_483_647 - $totalInBani;

            if (
                $unitPrice === null ||
                $unitPrice <= 0 ||
                $unitPrice > intdiv($remaining, $quantity)
            ) {
                return new JsonResponse([
                    'message' => 'Prețul sau totalul comenzii nu este valid.',
                ], 422);
            }

            $totalInBani += $unitPrice * $quantity;

            $items[] = (new OrderItem())
                ->setProductName($product->getName())
                ->setVariantName($variant->getName())
                ->setSku($variant->getSku())
                ->setQuantity($quantity)
                ->setUnitPriceInBani($unitPrice);
        }

        // 5. Construim comanda și legăm pozițiile de ea.
        $order = (new Order())
            ->setCustomerName(trim($data['customerName']))
            ->setCustomerEmail(trim($data['customerEmail']))
            ->setCustomerPhone(trim($data['customerPhone']))
            ->setDeliveryMethod($data['deliveryMethod'])
            ->setDeliveryAddress(
                $data['deliveryMethod'] === 'delivery'
                    ? trim($data['deliveryAddress'])
                    : null,
            )
            ->setTotalInBani($totalInBani);

        // 6. Salvăm comanda și toate pozițiile împreună.
        $entityManager->persist($order);

        foreach ($items as $item) {
            $order->addItem($item);
            $entityManager->persist($item);
        }

        $entityManager->flush();

        return new JsonResponse([
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'totalInBani' => $order->getTotalInBani(),
        ], 201);
    }

    private function validateOrderData(array $data): array
    {
        $errors = [];

        foreach (
            [
                'customerName' => 150,
                'customerEmail' => 180,
                'customerPhone' => 30,
            ] as $field => $maxLength
        ) {
            $value = $data[$field] ?? null;

            if (
                !is_string($value) ||
                trim($value) === '' ||
                mb_strlen(trim($value)) > $maxLength
            ) {
                $errors[$field] = "Câmp obligatoriu, maximum $maxLength caractere.";
            }
        }

        if (
            !isset($errors['customerEmail']) &&
            filter_var(trim($data['customerEmail']), FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors['customerEmail'] = 'Introdu o adresă de email validă.';
        }

        $method = $data['deliveryMethod'] ?? null;
        $address = $data['deliveryAddress'] ?? null;

        if (!in_array($method, ['pickup', 'delivery'], true)) {
            $errors['deliveryMethod'] = 'Alege ridicare sau livrare.';
        }

        if (
            ($address !== null && (!is_string($address) || mb_strlen($address) > 1000)) ||
            ($method === 'delivery' && (!is_string($address) || trim($address) === ''))
        ) {
            $errors['deliveryAddress'] = 'Pentru livrare, introdu o adresă de maximum 1000 de caractere.';
        }

        $items = $data['items'] ?? null;

        if (!is_array($items) || !array_is_list($items) || $items === []) {
            $errors['items'] = 'Adaugă cel puțin o variantă în coș.';

            return $errors;
        }

        $seenIds = [];

        foreach ($items as $index => $row) {
            if (!is_array($row)) {
                $errors["items.$index"] = 'Poziția trebuie să conțină o variantă și o cantitate.';
                continue;
            }

            $variantId = $row['variantId'] ?? null;
            $quantity = $row['quantity'] ?? null;

            if (!is_int($variantId) || $variantId < 1) {
                $errors["items.$index.variantId"] = 'ID-ul trebuie să fie un întreg pozitiv.';
            } elseif (isset($seenIds[$variantId])) {
                $errors["items.$index.variantId"] = 'Varianta apare deja în comandă.';
            } else {
                $seenIds[$variantId] = true;
            }

            if (!is_int($quantity) || $quantity < 1 || $quantity > 2_147_483_647) {
                $errors["items.$index.quantity"] = 'Cantitatea trebuie să fie un întreg pozitiv valid.';
            }
        }

        return $errors;
    }
}
