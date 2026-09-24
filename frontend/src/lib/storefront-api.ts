import { api } from './api'
import type { Paginated } from './products-api'

export interface ShopProduct {
  id: number
  name: string
  slug: string
  category: string | null
  material: string | null
  wood_type: string | null
  finish: string | null
  dimensions_label: string | null
  description: string | null
  base_price: string
  lead_time_days: number | null
  image_url: string | null
  has_model: boolean
  model_url?: string | null
  model_format?: string | null
}

export async function fetchShopProducts(params?: { category?: string; q?: string; page?: number }): Promise<Paginated<ShopProduct>> {
  const { data } = await api.get('/shop/products', { params })
  return data
}

export async function fetchShopProduct(id: number): Promise<ShopProduct> {
  const { data } = await api.get(`/shop/products/${id}`)
  return data.data
}
