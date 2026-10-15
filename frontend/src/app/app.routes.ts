import { Routes } from '@angular/router';
import { Home } from './pages/home/home';
import { Products } from './pages/products/products';
import { About } from './pages/about/about';
import { Articles } from './pages/articles/articles';
import { Contact } from './pages/contact/contact';
import { ProductDetail } from './pages/product-detail/product-detail';
import { ArticleDetail } from './pages/article-detail/article-detail';
import { Login } from './pages/login/login';
import { Admin } from './pages/admin/admin';
import { adminGuard } from './guards/admin-guard';

export const routes: Routes = [
  { path: '', component: Home },
  { path: 'products', component: Products },
  { path: 'about', component: About },
  { path: 'articles', component: Articles },
  { path: 'contact', component: Contact },
  { path: 'products/:id', component: ProductDetail },
  { path: 'articles/:slug', component: ArticleDetail },
  { path: 'login', component: Login },
  { path: 'admin', component: Admin, canActivate: [adminGuard] },
];
