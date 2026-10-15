import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../services/auth';
import { Router } from '@angular/router';

@Component({
  selector: 'app-login',
  imports: [FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  email = '';
  password = '';

  loading = signal(false);
  error = signal('');
  success = signal('');

  onSubmit(): void {
    if (this.loading()) {
      return;
    }

    this.error.set('');
    this.success.set('');
    this.loading.set(true);

    this.authService.login(this.email.trim(), this.password).subscribe({
      next: () => {
        this.password = '';
        this.loading.set(false);
        this.success.set('Autentificare reușită.');
        this.router.navigate(['/admin']);
      },
      error: (error) => {
        this.password = '';
        this.loading.set(false);

        this.error.set(
          error.status === 401
            ? 'E-mail sau parolă incorecte ori cont indisponibil.'
            : 'Autentificarea nu a putut fi realizată. Încearcă din nou.',
        );
      },
    });
  }
}
