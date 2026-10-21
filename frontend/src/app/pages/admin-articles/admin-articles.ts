import { Component, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth';
import { ArticleService } from '../../services/article';
import { Article } from '../../models/article';

@Component({
  selector: 'app-admin-articles',
  imports: [RouterLink],
  templateUrl: './admin-articles.html',
  styleUrl: './admin-articles.css',
})
export class AdminArticles {
  private readonly articleService = inject(ArticleService);
  articles = signal<Article[]>([]);

  articleId: number | null = null;
  loading = signal(false);
  error = signal('');
  success = signal('');

  allArticles = signal<Article[]>([]);
  articlesLoading = signal(false);
  articlesError = signal('');

  ngOnInit(): void {
    this.getAllArticles();
  }

  getAllArticles(): void {
    if (this.articlesLoading()) {
      return;
    }

    this.articlesError.set('');
    this.articlesLoading.set(true);

    this.articleService.getAllArticlesAdmin().subscribe({
      next: (receivedArticles) => {
        this.allArticles.set(receivedArticles);
        this.articlesLoading.set(false);
      },
      error: () => {
        this.articlesError.set('Articolele nu au putut fi încărcate.');
        this.articlesLoading.set(false);
      },
    });
  }

  deleteArticle(id: number): void {
    if (this.articlesLoading()) {
      return;
    }

    if (!window.confirm('Sigur vrei să ștergi acest articol?')) {
      return;
    }

    this.articlesError.set('');
    this.articlesLoading.set(true);

    this.articleService.deleteArticle(id).subscribe({
      next: () => {
        this.allArticles.update((articles) => articles.filter((article) => article.id !== id));

        this.articlesLoading.set(false);
      },
      error: (error) => {
        console.error('Eroare la ștergere:', error);

        this.articlesError.set(
          error.error?.message ?? `Articolul nu a putut fi șters. Cod HTTP: ${error.status}`,
        );

        this.articlesLoading.set(false);
      },
    });
  }
}
