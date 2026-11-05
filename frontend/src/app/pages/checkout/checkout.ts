import { Component, signal } from '@angular/core';
import { inject } from '@angular/core';
import { CartService } from '../../services/cart';
import { CreateOrderResponse, OrderService, type CreateOrderData } from '../../services/order';
import { FormsModule } from '@angular/forms';
import { DecimalPipe } from '@angular/common';

@Component({
  selector: 'app-checkout',
  imports: [FormsModule, DecimalPipe],
  templateUrl: './checkout.html',
  styleUrl: './checkout.css',
})
export class Checkout {
  readonly cart = inject(CartService);
  private readonly orderService = inject(OrderService);
  customerName = '';
  customerEmail = '';
  customerPhone = '';
  deliveryMethod: 'pickup' | 'delivery' = 'pickup';
  deliveryAddress = '';

  readonly confirmedOrder = signal<CreateOrderResponse | null>(null);
  readonly submitting = signal(false);
  readonly orderError = signal('');

  addOrder(): void {
    if (this.submitting() || this.confirmedOrder()) {
      return;
    }

    this.orderError.set('');
    const items = this.cart.items().map((item) => ({
      variantId: item.variantId,
      quantity: item.quantity,
    }));

    if (items.length === 0) {
      return;
    }

    const data: CreateOrderData = {
      customerName: this.customerName.trim(),
      customerEmail: this.customerEmail.trim(),
      customerPhone: this.customerPhone.trim(),
      deliveryMethod: this.deliveryMethod,
      deliveryAddress: this.deliveryMethod === 'delivery' ? this.deliveryAddress.trim() : null,
      items,
    };

    this.submitting.set(true);

    this.orderService.createOrder(data).subscribe({
      next: (order) => {
        this.confirmedOrder.set(order);
        this.cart.clear();
        this.submitting.set(false);
      },
      error: () => {
        this.orderError.set('Comanda nu a putut fi trimisă. Încearcă din nou.');
        this.submitting.set(false);
      },
    });
  }
}
