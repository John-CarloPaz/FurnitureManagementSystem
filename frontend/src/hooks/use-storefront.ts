import { useQuery } from '@tanstack/react-query'
import { fetchShopCategories, fetchShopProduct, fetchShopProducts, type ShopProductsParams } from '@/lib/storefront-api'

export function useShopProducts(params?: ShopProductsParams) {
  return useQuery({ queryKey: ['shop-products', params], queryFn: () => fetchShopProducts(params) })
}

export function useShopProduct(id: number) {
  return useQuery({ queryKey: ['shop-product', id], queryFn: () => fetchShopProduct(id), enabled: !!id })
}

export function useShopCategories() {
  return useQuery({ queryKey: ['shop-categories'], queryFn: fetchShopCategories, staleTime: 5 * 60 * 1000 })
}
