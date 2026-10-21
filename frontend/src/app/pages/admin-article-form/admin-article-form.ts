import { FormsModule } from '@angular/forms';
import { Component, inject, signal } from '@angular/core';
import { ArticleService, CreateArticleData } from '../../services/article';
import { OnInit } from '@angular/core';
import { Observable } from 'rxjs';
import { ActivatedRoute, Router } from '@angular/router';
@Component({
  selector: 'app-admin-article-form',
  imports: [FormsModule],
  templateUrl: './admin-article-form.html',
  styleUrl: './admin-article-form.css',
})
export class AdminArticleForm {
  private readonly articleService = inject(ArticleService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  articleId: number | null = null;
  title = '';
  summary = '';
  content = '';
  category = '';
  published = false;
  currentImagePath: string | null = null;

  loading = signal(false);
  error = signal('');
  success = signal('');

  selectedImage: File | null = null;
  imageAlt = '';

  data = {};

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');

    if (id === null) {
      return;
    }

    const parsedId = Number(id);

    if (!Number.isSafeInteger(parsedId) || parsedId <= 0) {
      this.error.set('ID-ul articolului nu este valid.');
      return;
    }

    this.articleId = parsedId;
    this.loading.set(true);

    this.articleService.getAdminArticle(parsedId).subscribe({
      next: (article) => {
        this.title = article.title;
        this.summary = article.summary;
        this.content = article.content;
        this.category = article.category;
        this.published = article.published;
        this.currentImagePath = article.image_path ?? null;
        this.imageAlt = article.image_alt ?? '';

        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.error.set('Articolul nu a putut fi încărcat.');
      },
    });
  }

  saveArticle(): void {
    if (this.loading()) {
      return;
    }

    this.error.set('');
    this.success.set('');
    this.loading.set(true);

    const data: CreateArticleData = {
      title: this.title,
      summary: this.summary,
      content: this.content,
      category: this.category,
      published: this.published,
    };

    const isEditing = this.articleId !== null;
    const image = this.selectedImage;
    const imageAlt = this.imageAlt;

    const request$: Observable<{ id: number }> =
      this.articleId !== null
        ? this.articleService.updateArticle(this.articleId, data)
        : this.articleService.createArticle(data);

    request$.subscribe({
      next: (article) => {
        // Păstrăm ID-ul: o nouă salvare va actualiza articolul.
        this.articleId = article.id;

        const finishSaving = (): void => {
          this.loading.set(false);
          this.router.navigate(['/admin/articles']);

          this.success.set(
            isEditing
              ? 'Modificările au fost salvate.'
              : data.published
                ? 'Articolul a fost publicat.'
                : 'Articolul a fost salvat ca ciornă.',
          );
        };

        if (image === null) {
          finishSaving();
          return;
        }

        this.articleService.uploadArticleImage(article.id, image, imageAlt).subscribe({
          next: () => {
            this.selectedImage = null;
            finishSaving();
          },
          error: () => {
            this.loading.set(false);
            this.error.set(
              'Articolul a fost salvat, dar imaginea nu a putut fi încărcată. Apasă din nou „Salvează modificările” pentru a reîncerca.',
            );
          },
        });
      },
      error: (error) => {
        this.loading.set(false);

        this.error.set(
          error.status === 401
            ? 'Sesiunea a expirat. Autentifică-te din nou.'
            : (error.error?.message ?? 'Articolul nu a putut fi salvat.'),
        );
      },
    });
  }

  onImageSelected(event: Event): void {
    const input = event.target as HTMLInputElement;

    this.selectedImage = input.files?.[0] ?? null;
  }
}
