import { Component, inject, signal } from '@angular/core';
import { DecimalPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ProductService } from '../../services/product'; // verifică această cale
import { Product } from '../../models/product';

@Component({
  selector: 'app-home',
  imports: [RouterLink, DecimalPipe],
  templateUrl: './home.html',
  styleUrl: './home.css',
})
export class Home {
  private readonly productService = inject(ProductService);

  products = signal<Product[]>([]);
  productsLoading = signal(false);
  productsError = signal('');

  ngOnInit(): void {
    this.productsLoading.set(true);

    this.productService.getAll().subscribe({
      next: (products) => {
        this.products.set(products.slice(0, 4));
        this.productsLoading.set(false);
      },
      error: () => {
        this.productsError.set('Produsele nu au putut fi încărcate.');
        this.productsLoading.set(false);
      },
    });
  }

  getMinPrice(product: Product): number {
    return Math.min(...product.variants.map((variant) => variant.price_in_bani));
  }
}
