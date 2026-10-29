import { inject, Injectable } from '@angular/core';
import { AdminProduct, Product } from '../models/product';
import { Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root',
})
export class ProductService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = '/api';

  getAll(): Observable<Product[]> {
    return this.http.get<Product[]>(`${this.apiUrl}/products`);
  }

  getAdminProducts(): Observable<AdminProduct[]> {
    return this.http.get<AdminProduct[]>(`${this.apiUrl}/admin/products`);
  }
  getBySlug(slug: string): Observable<Product> {
    return this.http.get<Product>(`${this.apiUrl}/products/${encodeURIComponent(slug)}`);
  }
}
