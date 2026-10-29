import { DecimalPipe } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ProductService } from '../../services/product';
import { Product, Category } from '../../models/product';

@Component({
  selector: 'app-products',
  imports: [RouterLink, DecimalPipe],
  templateUrl: './products.html',
  styleUrl: './products.css',
})
export class Products implements OnInit {
  private readonly productService = inject(ProductService);

  selectedCategory = '';
  searchTerm = '';

  allProducts: Product[] = [];
  products = signal<Product[]>([]);
  categories = signal<Category[]>([]);

  loading = signal(false);
  error = signal('');

  ngOnInit(): void {
    this.loading.set(true);

    this.productService.getAll().subscribe({
      next: (products) => {
        this.allProducts = products;

        this.categories.set(
          Array.from(
            new Map(products.map((product) => [product.category.id, product.category])).values(),
          ),
        );

        this.applyFilters();
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Produsele nu au putut fi încărcate.');
        this.loading.set(false);
      },
    });
  }

  selectCategory(slug: string): void {
    this.selectedCategory = slug;
    this.applyFilters();
  }

  onSearch(event: Event): void {
    this.searchTerm = (event.target as HTMLInputElement).value;
    this.applyFilters();
  }

  private applyFilters(): void {
    const search = this.searchTerm.trim().toLowerCase();

    this.products.set(
      this.allProducts.filter((product) => {
        const matchesCategory =
          this.selectedCategory === '' || product.category.slug === this.selectedCategory;

        const matchesSearch = product.name.toLowerCase().includes(search);

        return matchesCategory && matchesSearch;
      }),
    );
  }

  getMinPrice(product: Product): number {
    return Math.min(...product.variants.map((variant) => variant.price_in_bani));
  }
}
