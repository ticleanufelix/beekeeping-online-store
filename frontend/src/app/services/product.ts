import { Injectable } from '@angular/core';
import { Product } from '../models/product';

@Injectable({
  providedIn: 'root',
})
export class ProductService {
  private readonly products: Product[] = [
    { id: 1, name: 'Miere de salcâm', price: 12.5 },
    { id: 2, name: 'Miere polifloră', price: 9.9 },
    { id: 3, name: 'Afumător apicol', price: 24.9 },
  ];

  getAll(): Product[] {
    return this.products;
  }

  getById(id: number): Product | undefined {
    return this.products.find((product) => product.id === id);
  }
}
