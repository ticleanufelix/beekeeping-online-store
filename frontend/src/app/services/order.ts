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

export interface CreateOrderResponse {
  id: number;
  status: string;
  totalInBani: number;
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
}
