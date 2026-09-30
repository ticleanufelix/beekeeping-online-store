import { Component, inject } from '@angular/core';
import { Product } from '../../models/product';
import { ProductService } from '../../services/product';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-products',
  imports: [RouterLink],
  templateUrl: './products.html',
  styleUrl: './products.css',
})
export class Products {
  selectedCategory = '';
  searchTerm = '';

  private readonly productService = inject(ProductService);

  products: Product[] = this.productService.getAll();

  selectCategory(category: string): void {
    this.selectedCategory = category;
    this.applyFilters();
  }

  onSearch(event: Event): void {
    this.searchTerm = (event.target as HTMLInputElement).value;
    this.applyFilters();
  }

  private applyFilters(): void {
    const byCategory = this.selectedCategory
      ? this.productService.getByCategory(this.selectedCategory)
      : this.productService.getAll();

    const search = this.searchTerm.trim().toLowerCase();

    this.products = byCategory.filter((product) => product.name.toLowerCase().includes(search));
  }
}
