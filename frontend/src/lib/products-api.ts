import { api, fileUrl } from './api'
import type { ProductStatus } from './status'

export interface ProductModel {
  id: number
  current_version_id: number | null
}

export interface ProductImage {
  id: number
  path: string
  is_primary: boolean
}

export interface Product {
  id: number
  name: string
  slug: string
  description: string | null
  category: string | null
  material: string | null
  wood_type: string | null
  finish: string | null
  width_cm: string | null
  depth_cm: string | null
  height_cm: string | null
  weight_kg: string | null
  dimensions_label: string | null
  base_price: string
  lead_time_days: number | null
  status: ProductStatus
  status_label: string
  allowed_status_transitions: ProductStatus[]
  published_at: string | null
  model?: ProductModel | null
  images?: ProductImage[]
  created_at: string
  updated_at: string
}

export interface ModelVersion {
  id: number
  version: number
  format: string
  file_size: number | null
  change_log: string | null
  uploaded_by: number
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number }
}

export async function fetchProducts(params?: { status?: string }): Promise<Paginated<Product>> {
  const { data } = await api.get('/products', { params })
  return data
}

export async function fetchProduct(id: number): Promise<Product> {
  const { data } = await api.get(`/products/${id}`)
  return data.data
}

export interface ProductInput {
  name: string
  description?: string | null
  category?: string | null
  material?: string | null
  wood_type?: string | null
  finish?: string | null
  width_cm?: number | null
  depth_cm?: number | null
  height_cm?: number | null
  weight_kg?: number | null
  base_price?: number
  lead_time_days?: number | null
}

export interface CatalogOptions {
  categories: string[]
  materials: string[]
  wood_types: string[]
  finishes: string[]
}

export async function fetchCatalogOptions(): Promise<CatalogOptions> {
  const { data } = await api.get('/products/options')
  return data.data
}

export async function createProduct(payload: ProductInput): Promise<Product> {
  const { data } = await api.post('/products', payload)
  return data.data
}

export async function updateProduct(id: number, payload: Partial<ProductInput>): Promise<Product> {
  const { data } = await api.patch(`/products/${id}`, payload)
  return data.data
}

export async function changeProductStatus(
  id: number,
  action: 'publish' | 'unpublish' | 'archive',
): Promise<Product> {
  const { data } = await api.post(`/products/${id}/${action}`)
  return data.data
}

export async function fetchProductVersions(productId: number): Promise<ModelVersion[]> {
  const { data } = await api.get(`/products/${productId}/model-versions`)
  return data.data
}

export async function uploadProductModel(productId: number, file: File, changeLog?: string): Promise<ModelVersion> {
  const form = new FormData()
  form.append('file', file)
  if (changeLog) form.append('change_log', changeLog)
  const { data } = await api.post(`/products/${productId}/model-versions`, form)
  return data.data
}

export async function getModelDownloadUrl(versionId: number): Promise<string> {
  const { data } = await api.get(`/model-versions/${versionId}/download`)
  return fileUrl(data.data.url)
}
