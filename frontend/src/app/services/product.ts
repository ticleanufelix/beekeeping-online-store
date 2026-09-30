import { Injectable } from '@angular/core';
import { Product } from '../models/product';

@Injectable({
  providedIn: 'root',
})
export class ProductService {
  private readonly products: Product[] = [
    {
      id: 1,
      name: 'Miere de salcâm',
      price: 12.5,
      description: 'Miere cu gust delicat, obținută din flori de salcâm.',
      category: 'Miere',
    },
    {
      id: 2,
      name: 'Miere polifloră',
      price: 9.9,
      description: 'Miere cu gust delicat, obținută din flori de salcâm.',
      category: 'Miere',
    },
    {
      id: 3,
      name: 'Afumător apicol',
      price: 24.9,
      description: 'Miere cu gust delicat, obținută din flori de salcâm.',
      category: 'Echipamente',
    },
    {
      id: 4,
      name: 'Afumător apicol',
      price: 24.9,
      description: 'Miere cu gust delicat, obținută din flori de salcâm.',
      category: 'Echipamente',
    },
  ];

  getAll(): Product[] {
    return this.products;
  }

  getById(id: number): Product | undefined {
    return this.products.find((product) => product.id === id);
  }

  getByCategory(category: string): Product[] {
    return this.products.filter((product) => product.category === category);
  }
}
