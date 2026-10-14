export interface Article {
  id: number;
  title: string;
  slug: string;
  summary: string;
  content: string;
  image_path: string | null;
  image_alt: string | null;
  category: string;
  created_at: string;
  updated_at: string;
  published_at: string | null;
}
