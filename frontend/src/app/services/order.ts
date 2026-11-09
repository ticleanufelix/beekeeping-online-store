import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, switchMap } from 'rxjs';

export interface CreateOrderData {
  customerName: string;
  customerEmail: string;
  customerPhone: string;
  deliveryMethod: 'pickup' | 'delivery';
  deliveryAddress?: string | null;
  items: { variantId: number; quantity: number }[];
}

export interface AdminOrderSummary {
  id: number;
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  delivery_method: 'pickup' | 'delivery';
  status: string;
  total_in_bani: number;
  created_at: string | null;
}

export interface AdminOrderItem {
  product_name: string;
  variant_name: string;
  sku: string;
  quantity: number;
  unit_price_in_bani: number;
  subtotal_in_bani: number;
}

export interface AdminOrderDetail extends AdminOrderSummary {
  delivery_address: string | null;
  items: AdminOrderItem[];
}

export interface CreateOrderResponse {
  id: number;
  status: string;
  totalInBani: number;
}

export type OrderStatus = 'new' | 'confirmed' | 'completed' | 'cancelled';

export interface EditOrder {
  id: number;
  status: OrderStatus;
}

@Injectable({ providedIn: 'root' })
export class OrderService {
  private readonly http = inject(HttpClient);

  createOrder(data: CreateOrderData): Observable<CreateOrderResponse> {
    return this.http.get<{ token: string }>('/api/csrf/orders/create').pipe(
      switchMap(({ token }) =>
        this.http.post<CreateOrderResponse>('/api/orders', data, {
          headers: { 'X-CSRF-TOKEN': token },
        }),
      ),
    );
  }

  getAdminOrders(): Observable<AdminOrderSummary[]> {
    return this.http.get<AdminOrderSummary[]>('/api/admin/orders');
  }

  getAdminOrder(id: number): Observable<AdminOrderDetail> {
    return this.http.get<AdminOrderDetail>(`/api/admin/orders/${id}`);
  }

  editOrder(id: number, status: OrderStatus): Observable<EditOrder> {
    return this.http
      .get<{ token: string }>(`/api/admin/csrf/orders/${id}/edit`)
      .pipe(
        switchMap(({ token }) =>
          this.http.patch<EditOrder>(
            `/api/admin/orders/${id}/status`,
            { status },
            { headers: { 'X-CSRF-TOKEN': token } },
          ),
        ),
      );
  }
}
