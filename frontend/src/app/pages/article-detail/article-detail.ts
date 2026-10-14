import { Component, inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { ActivatedRoute } from '@angular/router';
import { toSignal } from '@angular/core/rxjs-interop';
import { catchError, map, of, startWith, switchMap } from 'rxjs';
import { ArticleService } from '../../services/article';
import { Article } from '../../models/article';

interface ArticleState {
  loading: boolean;
  article: Article | null;
  error: string;
}

const initialState: ArticleState = {
  loading: true,
  article: null,
  error: '',
};

@Component({
  selector: 'app-article-detail',
  imports: [],
  templateUrl: './article-detail.html',
  styleUrl: './article-detail.css',
})
export class ArticleDetail {
  private readonly route = inject(ActivatedRoute);
  private readonly articleService = inject(ArticleService);

  state = toSignal(
    this.route.paramMap.pipe(
      switchMap((params) => {
        const slug = params.get('slug') ?? '';

        return this.articleService.getBySlug(slug).pipe(
          map((article): ArticleState => ({
            loading: false,
            article,
            error: '',
          })),

          catchError((error: HttpErrorResponse) =>
            of<ArticleState>({
              loading: false,
              article: null,
              error:
                error.status === 404
                  ? 'Articolul nu a fost găsit.'
                  : 'Articolul nu a putut fi încărcat. Încearcă din nou.',
            }),
          ),

          startWith(initialState),
        );
      }),
    ),
    { initialValue: initialState },
  );
}
