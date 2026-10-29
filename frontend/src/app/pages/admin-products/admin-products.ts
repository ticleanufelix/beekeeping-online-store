import { Component, inject, signal } from '@angular/core';
import { ProductService } from '../../services/product';
import { AdminProduct, Product } from '../../models/product';
import { DatePipe, DecimalPipe } from '@angular/common';

@Component({
  selector: 'app-admin-products',
  imports: [DecimalPipe, DatePipe],
  templateUrl: './admin-products.html',
  styleUrl: './admin-products.css',
})
export class AdminProducts {
  private readonly productService = inject(ProductService);

  productId: number | null = null;
  loading = signal(false);
  error = signal('');
  success = signal('');

  allProducts = signal<AdminProduct[]>([]);
  productsLoading = signal(false);
  productsError = signal('');

  ngOnInit() {
    this.getAllProducts();
  }

  getAllProducts() {
    if (this.productsLoading()) {
      return;
    }
    this.productsError.set('');
    this.productsLoading.set(true);

    this.productService.getAdminProducts().subscribe({
      next: (recivedProducts) => {
        this.allProducts.set(recivedProducts);
        this.productsLoading.set(false);
      },
      error: () => {
        this.productsError.set('Produsele nu au putut fi încărcate.');
        this.productsLoading.set(false);
      },
    });
  }
}
