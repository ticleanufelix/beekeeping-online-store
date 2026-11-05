<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\OrderRepository;


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
}
