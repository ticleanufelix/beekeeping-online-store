import { Component, inject } from '@angular/core';
import { ArticleService } from '../../services/article';
import { Article } from '../../models/article';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-articles',
  imports: [RouterLink],
  templateUrl: './articles.html',
  styleUrl: './articles.css',
})
export class Articles {
  selectedCategory = '';
  searchTerm = '';

  private readonly articleService = inject(ArticleService);
  articles: Article[] = this.articleService.getAll();

  selectCategory(category: string): void {
    this.selectedCategory = category;
    this.applyFilters();
  }

  onSearch(event: Event): void {
    this.searchTerm = (event.target as HTMLInputElement).value;
    this.applyFilters();
  }

  private applyFilters() {
    const byCategory = this.selectedCategory
      ? this.articleService.getByCategory(this.selectedCategory)
      : this.articleService.getAll();

    const search = this.searchTerm.trim().toLowerCase();

    this.articles = byCategory.filter((product) => product.title.toLowerCase().includes(search));
  }
}
