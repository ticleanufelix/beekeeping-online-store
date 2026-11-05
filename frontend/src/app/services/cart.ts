import { computed, Injectable, signal } from '@angular/core';

export interface CartItem {
  variantId: number;
  productName: string;
  variantName: string;
  priceInBani: number;
  quantity: number;
}

@Injectable({ providedIn: 'root' })
export class CartService {
  private readonly storageKey = 'manolcom-cart';
  private readonly cartItems = signal<CartItem[]>(this.loadCart());

  readonly items = this.cartItems.asReadonly();

  readonly totalQuantity = computed(() =>
    this.items().reduce((total, item) => total + item.quantity, 0),
  );

  readonly totalInBani = computed(() =>
    this.items().reduce((total, item) => total + item.priceInBani * item.quantity, 0),
  );

  add(item: CartItem): void {
    if (!Number.isSafeInteger(item.quantity) || item.quantity < 1) {
      return;
    }

    const items = this.cartItems();
    const existing = items.find((row) => row.variantId === item.variantId);

    const updated = existing
      ? items.map((row) =>
          row.variantId === item.variantId
            ? { ...item, quantity: row.quantity + item.quantity }
            : row,
        )
      : [...items, item];

    this.saveCart(updated);
  }

  private loadCart(): CartItem[] {
    try {
      const saved = localStorage.getItem(this.storageKey);
      const items = saved ? JSON.parse(saved) : [];

      return Array.isArray(items) ? items : [];
    } catch {
      return [];
    }
  }

  setQuantity(variantId: number, quantity: number): void {
    if (!Number.isSafeInteger(quantity) || quantity < 1) {
      return;
    }

    const updated = this.cartItems().map((item) =>
      item.variantId === variantId ? { ...item, quantity } : item,
    );

    this.saveCart(updated);
  }

  remove(variantId: number): void {
    this.saveCart(this.cartItems().filter((item) => item.variantId !== variantId));
  }

  private saveCart(items: CartItem[]): void {
    this.cartItems.set(items);
    localStorage.setItem(this.storageKey, JSON.stringify(items));
  }

  clear(): void {
    this.saveCart([]);
  }
}
