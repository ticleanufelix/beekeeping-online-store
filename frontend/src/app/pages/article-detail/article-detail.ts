import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ArticleService } from '../../services/article';
import { toSignal } from '@angular/core/rxjs-interop';
import { map } from 'rxjs';

@Component({
  selector: 'app-article-detail',
  imports: [],
  templateUrl: './article-detail.html',
  styleUrl: './article-detail.css',
})
export class ArticleDetail {
  private readonly route = inject(ActivatedRoute);
  private readonly articleService = inject(ArticleService);

  article = toSignal(
    this.route.paramMap.pipe(
      map((params) => {
        const slug = params.get('slug') ?? '';
        return this.articleService.getBySlug(slug);
      }),
    ),
  );
}
