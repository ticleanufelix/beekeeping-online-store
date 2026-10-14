import { Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ArticleService } from '../../services/article';
import { Article } from '../../models/article';

@Component({
  selector: 'app-articles',
  imports: [RouterLink],
  templateUrl: './articles.html',
  styleUrl: './articles.css',
})
export class Articles implements OnInit {
  private readonly articleService = inject(ArticleService);

  // Lista completă primită de la backend.
  private allArticles: Article[] = [];

  // Lista afișată în pagină.
  articles = signal<Article[]>([]);

  loading = signal(true);
  error = signal('');

  selectedCategory = '';
  searchTerm = '';

  ngOnInit(): void {
    this.articleService.getAll().subscribe({
      next: (receivedArticles) => {
        this.allArticles = receivedArticles;
        this.applyFilters();
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Articolele nu au putut fi încărcate.');
        this.loading.set(false);
      },
    });
  }

  selectCategory(category: string): void {
    this.selectedCategory = category;
    this.applyFilters();
  }

  onSearch(event: Event): void {
    this.searchTerm = (event.target as HTMLInputElement).value;
    this.applyFilters();
  }

  private applyFilters(): void {
    const search = this.searchTerm.trim().toLowerCase();

    const filteredArticles = this.allArticles.filter((article) => {
      const matchesCategory =
        this.selectedCategory === '' || article.category === this.selectedCategory;

      const matchesSearch = article.title.toLowerCase().includes(search);

      return matchesCategory && matchesSearch;
    });

    this.articles.set(filteredArticles);
  }
}
