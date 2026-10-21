import { Component, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService } from '../../services/auth';
import { ArticleService } from '../../services/article';
import { Article } from '../../models/article';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-admin',
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './admin.html',
  styleUrl: './admin.css',
})
export class Admin {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly articleService = inject(ArticleService);

  loading = signal(false);
  error = signal('');

  onLogout(): void {
    if (this.loading()) {
      return;
    }

    this.loading.set(true);
    this.error.set('');

    this.authService.logout().subscribe({
      next: () => {
        this.loading.set(false);
        this.router.navigate(['/login']);
      },
      error: (error: HttpErrorResponse) => {
        this.loading.set(false);

        if (error.status === 401) {
          this.router.navigate(['/login']);
          return;
        }

        this.error.set('Deconectarea nu a reușit. Încearcă din nou.');
      },
    });
  }
}
