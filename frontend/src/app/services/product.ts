import { inject, Injectable } from '@angular/core';
import { AdminProduct, Category, Product } from '../models/product';
import { Observable, switchMap } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { CreateArticleData } from './article';

export interface CreateProductVariantData {
  name: string;
  price_in_bani: number;
  stock_quantity: number | null;
  available: boolean;
}

export interface CreateProductData {
  name: string;
  summary: string;
  description: string;
  category_id: number;
  active: boolean;
  variants: CreateProductVariantData[];
}

export interface CreateProductResponse {
  id: number;
  name: string;
  slug: string;
  active: boolean;
}

export interface UpdateProductVariantData extends CreateProductVariantData {
  id?: number;
}

export type UpdateProductData = Partial<Omit<CreateProductData, 'variants'>> & {
  variants?: UpdateProductVariantData[];
};

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

  getAdminProduct(id: number): Observable<AdminProduct> {
    return this.http.get<AdminProduct>(`${this.apiUrl}/admin/products/${id}`);
  }

  getBySlug(slug: string): Observable<Product> {
    return this.http.get<Product>(`${this.apiUrl}/products/${encodeURIComponent(slug)}`);
  }

  deleteAdminProduct(id: number): Observable<void> {
    return this.http.get<{ token: string }>(`${this.apiUrl}/admin/csrf/products/${id}/delete`).pipe(
      switchMap((response) =>
        this.http.delete<void>(`${this.apiUrl}/admin/products/${id}`, {
          headers: {
            'X-CSRF-TOKEN': response.token,
          },
        }),
      ),
    );
  }

  createProduct(data: CreateProductData): Observable<CreateProductResponse> {
    return this.http.get<{ token: string }>(`${this.apiUrl}/admin/csrf/products/create`).pipe(
      switchMap((response) =>
        this.http.post<CreateProductResponse>(`${this.apiUrl}/admin/products`, data, {
          headers: {
            'X-CSRF-TOKEN': response.token,
          },
        }),
      ),
    );
  }

  editProduct(id: number, data: UpdateProductData): Observable<AdminProduct> {
    return this.http.get<{ token: string }>(`${this.apiUrl}/admin/csrf/products/${id}/edit`).pipe(
      switchMap((response) =>
        this.http.patch<AdminProduct>(`${this.apiUrl}/admin/products/${id}`, data, {
          headers: {
            'X-CSRF-TOKEN': response.token,
          },
        }),
      ),
    );
  }

  getCategories(): Observable<Category[]> {
    return this.http.get<Category[]>(`${this.apiUrl}/categories`);
  }

  uploadProductImage(
    id: number,
    image: File,
    imageAlt: string,
  ): Observable<{ image_path: string; image_alt: string }> {
    const formData = new FormData();
    formData.append('image', image);
    formData.append('image_alt', imageAlt);

    return this.http.get<{ token: string }>(`${this.apiUrl}/admin/csrf/products/${id}/image`).pipe(
      switchMap((response) =>
        this.http.post<{ image_path: string; image_alt: string }>(
          `${this.apiUrl}/admin/products/${id}/image`,
          formData,
          {
            headers: {
              'X-CSRF-TOKEN': response.token,
            },
          },
        ),
      ),
    );
  }
}
