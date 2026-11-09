import { Component, computed, inject, signal } from '@angular/core';
import { OrderService, OrderStatus, type AdminOrderSummary } from '../../services/order';
import { DatePipe, DecimalPipe } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-admin-orders',
  imports: [DatePipe, DecimalPipe, RouterLink],
  templateUrl: './admin-orders.html',
  styleUrl: './admin-orders.css',
})
export class AdminOrders {
  private readonly orderService = inject(OrderService);

  orderId: number | null = null;
  loading = signal(false);
  error = signal('');
  success = signal('');

  readonly allOrders = signal<AdminOrderSummary[]>([]);
  ordersLoading = signal(false);
  ordersError = signal('');

  readonly tabs: { status: OrderStatus; label: string }[] = [
    { status: 'new', label: 'Noi' },
    { status: 'confirmed', label: 'În desfășurare' },
    { status: 'completed', label: 'Finalizate' },
    { status: 'cancelled', label: 'Anulate' },
  ];

  readonly selectedStatus = signal<OrderStatus>('new');

  readonly filteredOrders = computed(() => {
    const status = this.selectedStatus();

    return this.allOrders().filter((order) => order.status === status);
  });

  ngOnInit(): void {
    this.getAllOrders();
  }

  getAllOrders() {
    if (this.ordersLoading()) {
      return;
    }
    this.ordersError.set('');
    this.ordersLoading.set(true);

    this.orderService.getAdminOrders().subscribe({
      next: (recivedOrders) => {
        this.allOrders.set(recivedOrders);
        this.ordersLoading.set(false);
      },
      error: () => {
        this.ordersError.set('Comenzile nu au fost gasite');
        this.ordersLoading.set(false);
      },
    });
  }
}
