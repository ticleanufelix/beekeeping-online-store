import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, switchMap } from 'rxjs';

interface LoginResponse {
  message: string;
  user: {
    id: number;
    email: string;
    roles: string[];
  };
}

export interface AuthUser {
  id: number;
  email: string;
  roles: string[];
}

interface LoginResponse {
  message: string;
  user: AuthUser;
}

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = '/api';

  login(email: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}/login`, { email, password });
  }
  me(): Observable<AuthUser> {
    return this.http.get<AuthUser>(`${this.apiUrl}/me`);
  }

  logout(): Observable<{ message: string }> {
    return this.http.get<{ token: string }>(`${this.apiUrl}/csrf/logout`).pipe(
      switchMap((response) =>
        this.http.post<{ message: string }>(
          `${this.apiUrl}/logout`,
          {},
          {
            headers: {
              'X-CSRF-TOKEN': response.token,
            },
          },
        ),
      ),
    );
  }
}
