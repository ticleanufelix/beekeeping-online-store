import { Component, inject, signal } from '@angular/core';
import { AdminOrderDetail, OrderService, OrderStatus } from '../../services/order';
import { ActivatedRoute } from '@angular/router';
import { toSignal } from '@angular/core/rxjs-interop';
import { catchError, combineLatest, map, of, startWith, Subject, switchMap } from 'rxjs';
import { HttpErrorResponse } from '@angular/common/http';
import { DatePipe, DecimalPipe } from '@angular/common';

interface OrderState {
  loading: boolean;
  order: AdminOrderDetail | null;
  error: string;
}

const initialState: OrderState = {
  loading: true,
  order: null,
  error: '',
};

@Component({
  selector: 'app-admin-order-details',
  imports: [DatePipe, DecimalPipe],
  templateUrl: './admin-order-details.html',
  styleUrl: './admin-order-details.css',
})
export class AdminOrderDetails {
  private readonly orderService = inject(OrderService);
  private readonly route = inject(ActivatedRoute);

  private readonly reloadOrder = new Subject<void>();

  readonly savingStatus = signal(false);
  readonly statusError = signal('');

  state = toSignal(
    combineLatest([this.route.paramMap, this.reloadOrder.pipe(startWith(undefined))]).pipe(
      switchMap(([params]) => {
        const id = Number(params.get('id'));

        if (!Number.isSafeInteger(id) || id < 1) {
          return of<OrderState>({
            loading: false,
            order: null,
            error: 'ID-ul comenzii nu este valid.',
          });
        }
        return this.orderService.getAdminOrder(id).pipe(
          map((order): OrderState => ({
            loading: false,
            order,
            error: '',
          })),
          catchError((error: HttpErrorResponse) =>
            of<OrderState>({
              loading: false,
              order: null,
              error:
                error.status === 404
                  ? 'Comanda nu a fost găsită'
                  : 'Comanda nu a putut fi încărcată. Încearcă din nou',
            }),
          ),
          startWith(initialState),
        );
      }),
    ),
    { initialValue: initialState },
  );

  changeStatus(id: number, status: OrderStatus): void {
    if (this.savingStatus()) {
      return;
    }

    this.statusError.set('');
    this.savingStatus.set(true);

    this.orderService.editOrder(id, status).subscribe({
      next: () => {
        this.reloadOrder.next();
        this.savingStatus.set(false);
      },
      error: (error: HttpErrorResponse) => {
        this.statusError.set(error.error?.message ?? 'Starea comenzii nu a putut fi schimbată.');
        this.savingStatus.set(false);
      },
    });
  }
}
