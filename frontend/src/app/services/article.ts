import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, switchMap } from 'rxjs';
import { Article } from '../models/article';

export interface CreateArticleData {
  title: string;
  summary: string;
  content: string;
  category: string;
  published: boolean;
}

interface CreateArticleResponse {
  id: number;
  title: string;
  slug: string;
  published: boolean;
}

@Injectable({
  providedIn: 'root',
})
export class ArticleService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = '/api';

  getAll(): Observable<Article[]> {
    return this.http.get<Article[]>(`${this.apiUrl}/articles`);
  }
  getBySlug(slug: string): Observable<Article> {
    return this.http.get<Article>(`${this.apiUrl}/articles/${encodeURIComponent(slug)}`);
  }

  getAllArticlesAdmin(): Observable<Article[]> {
    return this.http.get<Article[]>(`${this.apiUrl}/admin/articles`);
  }

  createArticle(data: CreateArticleData): Observable<CreateArticleResponse> {
    return this.http.get<{ token: string }>(`${this.apiUrl}/admin/csrf/articles/create`).pipe(
      switchMap((response) =>
        this.http.post<CreateArticleResponse>(`${this.apiUrl}/admin/articles`, data, {
          headers: {
            'X-CSRF-TOKEN': response.token,
          },
        }),
      ),
    );
  }

  getAdminArticle(id: number): Observable<Article> {
    return this.http.get<Article>(`${this.apiUrl}/admin/articles/${id}`);
  }

  updateArticle(id: number, data: Partial<CreateArticleData>): Observable<Article> {
    return this.http.get<{ token: string }>(`${this.apiUrl}/admin/csrf/articles/${id}/edit`).pipe(
      switchMap((response) =>
        this.http.patch<Article>(`${this.apiUrl}/admin/articles/${id}`, data, {
          headers: {
            'X-CSRF-TOKEN': response.token,
          },
        }),
      ),
    );
  }

  deleteArticle(id: number): Observable<void> {
    return this.http.get<{ token: string }>(`${this.apiUrl}/admin/csrf/articles/${id}/delete`).pipe(
      switchMap((response) =>
        this.http.delete<void>(`${this.apiUrl}/admin/articles/${id}`, {
          headers: {
            'X-CSRF-TOKEN': response.token,
          },
        }),
      ),
    );
  }

  uploadArticleImage(
    id: number,
    image: File,
    imageAlt: string,
  ): Observable<{ image_path: string; image_alt: string }> {
    const formData = new FormData();
    formData.append('image', image);
    formData.append('image_alt', imageAlt);

    return this.http.get<{ token: string }>(`${this.apiUrl}/admin/csrf/articles/${id}/image`).pipe(
      switchMap((response) =>
        this.http.post<{ image_path: string; image_alt: string }>(
          `${this.apiUrl}/admin/articles/${id}/image`,
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
