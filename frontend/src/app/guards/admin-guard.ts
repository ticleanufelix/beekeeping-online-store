import { inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { AuthService } from '../services/auth';

export const adminGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  return authService.me().pipe(
    map((user) => {
      if (user.roles.includes('ROLE_ADMIN')) {
        return true;
      }

      return router.createUrlTree(['/']);
    }),

    catchError((error: HttpErrorResponse) => {
      if (error.status === 401) {
        return of(router.createUrlTree(['/login']));
      }

      // Dacă nu putem verifica sesiunea, blocăm navigarea.
      return of(false);
    }),
  );
};
