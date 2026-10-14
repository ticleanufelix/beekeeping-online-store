import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Article } from '../models/article';

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
}
