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
  private readonly productService = inject(ProductService);

  products: Product[] = this.productService.getAll();

  selectCategory(category: string): void {
    this.selectedCategory = category;
    this.products = category
      ? this.productService.getByCategory(category)
      : this.productService.getAll();
  }
}
