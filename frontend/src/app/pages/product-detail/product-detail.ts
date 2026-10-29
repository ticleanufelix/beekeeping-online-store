import { DecimalPipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ProductService } from '../../services/product';
import { toSignal } from '@angular/core/rxjs-interop';
import { catchError, map, of, startWith, switchMap } from 'rxjs';

@Component({
  selector: 'app-product-detail',
  imports: [DecimalPipe],
  templateUrl: './product-detail.html',
  styleUrl: './product-detail.css',
})
export class ProductDetail {
  private readonly route = inject(ActivatedRoute);
  private readonly productService = inject(ProductService);

  readonly state = toSignal(
    this.route.paramMap.pipe(
      switchMap((params) => {
        const slug = params.get('slug');

        if (!slug) {
          return of({
            status: 'error' as const,
            message: 'Produsul nu a fost găsit.',
          });
        }

        return this.productService.getBySlug(slug).pipe(
          map((product) => ({
            status: 'success' as const,
            product,
          })),
          catchError((error) =>
            of({
              status: 'error' as const,
              message:
                error.status === 404
                  ? 'Produsul nu a fost găsit.'
                  : 'Produsul nu a putut fi încărcat.',
            }),
          ),
          startWith({ status: 'loading' as const }),
        );
      }),
    ),
    { initialValue: { status: 'loading' as const } },
  );
}
