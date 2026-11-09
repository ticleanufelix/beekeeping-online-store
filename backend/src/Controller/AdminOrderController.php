<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminOrderController
{
    #[Route('/api/admin/orders', methods: ['GET'])]
    public function getOrders(OrderRepository $orderRepository): JsonResponse
    {
        $orders = $orderRepository->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC']);
        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'id' => $order->getId(),
                'customer_name' => $order->getCustomerName(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_phone' => $order->getCustomerPhone(),
                'delivery_method' => $order->getDeliveryMethod(),
                'status' => $order->getStatus(),
                'total_in_bani' => $order->getTotalInBani(),
                'created_at' => $order->getCreatedAt()?->format(DATE_ATOM),

            ];
        }


        return new JsonResponse($data);
    }

    #[Route('/api/admin/orders/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getOrder(int $id, OrderRepository $orderRepository): JsonResponse
    {
        $order = $orderRepository->find($id);

        if ($order === null) {
            return new JsonResponse(['message' => 'Comanda nu a fost găsită.'], 404);
        }

        $items = [];

        foreach ($order->getItems() as $item) {
            $items[] = [
                'product_name' => $item->getProductName(),
                'variant_name' => $item->getVariantName(),
                'sku' => $item->getSku(),
                'quantity' => $item->getQuantity(),
                'unit_price_in_bani' => $item->getUnitPriceInBani(),
                'subtotal_in_bani' => $item->getUnitPriceInBani() * $item->getQuantity(),
            ];
        }

        return new JsonResponse([
            'id' => $order->getId(),
            'customer_name' => $order->getCustomerName(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_phone' => $order->getCustomerPhone(),
            'delivery_method' => $order->getDeliveryMethod(),
            'delivery_address' => $order->getDeliveryAddress(),
            'status' => $order->getStatus(),
            'total_in_bani' => $order->getTotalInBani(),
            'created_at' => $order->getCreatedAt()?->format(DATE_ATOM),
            'items' => $items,
        ]);
    }

    #[Route('/api/admin/orders/{id}/status', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function editStatus(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        OrderRepository $orderRepository
    ): JsonResponse {
        $order = $orderRepository->find($id);

        if ($order === null) {
            return new JsonResponse([
                'message' => 'Comanda nu a fost găsită.',
            ], 404);
        }

        if (!$this->isCsrfValid(
            $request,
            $csrfTokenManager,
            'admin_orders_edit_' . $id,
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

        $newStatus = $data['status'] ?? null;

        if (!in_array($newStatus, ['new', 'confirmed', 'completed', 'cancelled'], true)) {
            return new JsonResponse([
                'message' => 'Starea comenzii lipsește sau nu este validă.',
            ], 422);
        }

        $currentStatus = $order->getStatus();

        $allowedTransitions = [
            'new' => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        $allowedNextStatuses = $allowedTransitions[$currentStatus] ?? [];

        if (!in_array($newStatus, $allowedNextStatuses, true)) {
            return new JsonResponse([
                'message' => 'Comanda nu poate trece din starea curentă în starea selectată.',
            ], 409);
        }

        $order->setStatus($newStatus);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $order->getId(),
            'status' => $order->getStatus(),
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
}
