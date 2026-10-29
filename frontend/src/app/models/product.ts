export interface Category {
  id: number;
  name: string;
  slug: string;
}

export interface ProductVariant {
  id: number;
  name: string;
  sku: string;
  price_in_bani: number;
  stock_quantity: number | null;
  available: boolean;
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  summary: string;
  description: string;
  image_path: string | null;
  image_alt: string | null;
  category: Category;
  variants: ProductVariant[];
  created_at: string;
  updated_at: string;
}

export interface AdminProduct extends Product {
  active: boolean;
}
