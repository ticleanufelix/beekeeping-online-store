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
import { AdminArticles } from './pages/admin-articles/admin-articles';
import { AdminArticleForm } from './pages/admin-article-form/admin-article-form';
import { AdminHome } from './pages/admin-home/admin-home';
import { AdminProducts } from './pages/admin-products/admin-products';

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
  {
    path: 'admin',
    component: Admin,
    canActivate: [adminGuard],
    canActivateChild: [(route, state) => adminGuard(route, state)],
    children: [
      {
        path: '',
        pathMatch: 'full',
        component: AdminHome,
      },
      {
        path: 'articles',
        component: AdminArticles,
      },
      {
        path: 'articles/form',
        component: AdminArticleForm,
      },
      {
        path: 'articles/:id/edit',
        component: AdminArticleForm,
      },
      {
        path: 'products',
        component: AdminProducts,
      },
    ],
  },
];
