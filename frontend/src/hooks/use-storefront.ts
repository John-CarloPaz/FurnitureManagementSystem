import { useQuery } from '@tanstack/react-query'
import { fetchShopProduct, fetchShopProducts } from '@/lib/storefront-api'

export function useShopProducts(params?: { category?: string; q?: string }) {
  return useQuery({ queryKey: ['shop-products', params], queryFn: () => fetchShopProducts(params) })
}

export function useShopProduct(id: number) {
  return useQuery({ queryKey: ['shop-product', id], queryFn: () => fetchShopProduct(id), enabled: !!id })
}
