import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import {
  ProductService,
  UpdateProductVariantData,
  CreateProductData,
} from '../../services/product';
import { FormsModule } from '@angular/forms';
import { Category } from '../../models/product';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-admin-product-form',
  imports: [FormsModule],
  templateUrl: './admin-product-form.html',
  styleUrl: './admin-product-form.css',
})
export class AdminProductForm {
  private readonly productService = inject(ProductService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  categories = signal<Category[]>([]);
  categoriesLoading = signal(false);

  variants: UpdateProductVariantData[] = [
    {
      name: 'Standard',
      price_in_bani: 0,
      stock_quantity: null,
      available: true,
    },
  ];

  productId: number | null = null;
  name = '';
  summary = '';
  description = '';
  imageAlt = '';
  active = false;
  categoryId: number | null = null;

  loading = signal(false);
  error = signal('');
  success = signal('');
  currentImagePath: string | null = null;
  selectedImage: File | null = null;

  ngOnInit(): void {
    this.loadCategories();
    const id = this.route.snapshot.paramMap.get('id');

    if (id === null) {
      return;
    }

    const parsedId = Number(id);

    if (!Number.isSafeInteger(parsedId) || parsedId <= 0) {
      this.error.set('ID-ul articolului nu este valid.');
      return;
    }

    this.productId = parsedId;
    this.loading.set(true);

    this.productService.getAdminProduct(parsedId).subscribe({
      next: (product) => {
        this.name = product.name;
        this.summary = product.summary;
        this.description = product.description;
        this.currentImagePath = product.image_path ?? null;
        this.imageAlt = product.image_alt ?? '';
        this.active = product.active;
        this.categoryId = product.category.id;
        this.variants = product.variants.map((variant) => ({
          id: variant.id,
          name: variant.name,
          price_in_bani: variant.price_in_bani,
          stock_quantity: variant.stock_quantity,
          available: variant.available,
        }));

        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.error.set('ID-ul produsului nu este valid.');
      },
    });
  }

  addVariant(): void {
    this.variants.push({
      name: '',
      price_in_bani: 0,
      stock_quantity: null,
      available: true,
    });
  }

  loadCategories(): void {
    this.categoriesLoading.set(true);

    this.productService.getCategories().subscribe({
      next: (categories) => {
        this.categories.set(categories);
        this.categoriesLoading.set(false);
      },
      error: () => {
        this.error.set('Categoriile nu au putut fi încărcate.');
        this.categoriesLoading.set(false);
      },
    });
  }

  onImageSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    this.selectedImage = null;
    this.error.set('');

    if (file === null) {
      return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!allowedTypes.includes(file.type)) {
      this.error.set('Selectează o imagine JPG, PNG sau WebP.');
      input.value = '';
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      this.error.set('Imaginea poate avea maximum 5 MB.');
      input.value = '';
      return;
    }

    this.selectedImage = file;
  }

  saveProduct(): void {
    if (this.loading() || this.categoriesLoading()) {
      return;
    }

    this.error.set('');
    this.success.set('');

    if (this.categoryId === null) {
      this.error.set('Selectează categoria produsului.');
      return;
    }

    if (!this.name.trim() || !this.summary.trim() || !this.description.trim()) {
      this.error.set('Completează numele și descrierile produsului.');
      return;
    }

    if (this.variants.length === 0) {
      this.error.set('Adaugă cel puțin o variantă.');
      return;
    }

    const variants: UpdateProductVariantData[] = this.variants.map((variant) => ({
      ...(variant.id !== undefined ? { id: variant.id } : {}),
      name: variant.name.trim(),
      price_in_bani: Math.round(variant.price_in_bani),
      stock_quantity: variant.stock_quantity,
      available: variant.available,
    }));

    const invalidVariant = variants.some(
      (variant) =>
        !variant.name ||
        variant.name.length > 100 ||
        !Number.isSafeInteger(variant.price_in_bani) ||
        variant.price_in_bani <= 0 ||
        (variant.stock_quantity !== null &&
          (!Number.isSafeInteger(variant.stock_quantity) || variant.stock_quantity < 0)),
    );

    if (invalidVariant) {
      this.error.set(
        'Verifică variantele: nume obligatoriu de maximum 100 de caractere, preț pozitiv și stoc întreg pozitiv sau zero, ori gol.',
      );
      return;
    }

    const data: CreateProductData = {
      name: this.name.trim(),
      summary: this.summary.trim(),
      description: this.description.trim(),
      category_id: this.categoryId,
      active: this.active,
      variants,
    };

    const image = this.selectedImage;
    const imageAlt = this.imageAlt.trim();

    this.loading.set(true);

    const request$: Observable<{ id: number }> =
      this.productId !== null
        ? this.productService.editProduct(this.productId, data)
        : this.productService.createProduct(data);

    request$.subscribe({
      next: (product) => {
        // Păstrăm ID-ul, inclusiv dacă uploadul eșuează.
        // O nouă încercare va edita produsul deja creat.
        this.productId = product.id;

        const finishSaving = (): void => {
          this.loading.set(false);
          this.router.navigate(['/admin/products']);
        };

        if (image === null) {
          finishSaving();
          return;
        }

        this.productService.uploadProductImage(product.id, image, imageAlt).subscribe({
          next: (response) => {
            this.currentImagePath = response.image_path;
            this.selectedImage = null;

            finishSaving();
          },
          error: (error) => {
            this.loading.set(false);

            // Reîncărcăm produsul pentru a păstra ID-urile
            // variantelor create și a evita duplicate la reîncercare.
            this.refreshSavedVariants(product.id);

            this.error.set(
              'Produsul a fost salvat, dar imaginea nu a putut fi încărcată. ' +
                (error.error?.message ?? 'Încearcă din nou încărcarea.'),
            );
          },
        });
      },
      error: (error) => {
        this.loading.set(false);

        this.error.set(
          error.status === 401
            ? 'Sesiunea a expirat. Autentifică-te din nou.'
            : (error.error?.message ?? 'Produsul nu a putut fi salvat.'),
        );
      },
    });
  }

  private refreshSavedVariants(id: number): void {
    this.loading.set(true);

    this.productService.getAdminProduct(id).subscribe({
      next: (product) => {
        this.variants = product.variants.map((variant) => ({
          id: variant.id,
          name: variant.name,
          price_in_bani: variant.price_in_bani,
          stock_quantity: variant.stock_quantity,
          available: variant.available,
        }));

        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.error.set(
          'Produsul a fost salvat, dar datele nu au putut fi reîncărcate. Redeschide produsul din lista de administrare înainte de a salva din nou.',
        );
      },
    });
  }
}
