import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  changeProductStatus,
  createProduct,
  fetchCatalogOptions,
  fetchProduct,
  fetchProductVersions,
  fetchProducts,
  updateProduct,
  uploadProductModel,
  type ProductInput,
} from '@/lib/products-api'

export function useCatalogOptions() {
  return useQuery({
    queryKey: ['catalog-options'],
    queryFn: fetchCatalogOptions,
    staleTime: Infinity,
  })
}

export function useProducts(status?: string) {
  return useQuery({
    queryKey: ['products', { status }],
    queryFn: () => fetchProducts(status ? { status } : undefined),
  })
}

export function useProduct(id: number) {
  return useQuery({ queryKey: ['products', id], queryFn: () => fetchProduct(id), enabled: !!id })
}

export function useCreateProduct() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: ProductInput) => createProduct(payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products'] }),
  })
}

export function useUpdateProduct(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: Partial<ProductInput>) => updateProduct(id, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products', id] })
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}

export function useChangeProductStatus(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (action: 'publish' | 'unpublish' | 'archive') => changeProductStatus(id, action),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products', id] })
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}

export function useProductVersions(productId: number) {
  return useQuery({
    queryKey: ['products', productId, 'versions'],
    queryFn: () => fetchProductVersions(productId),
    enabled: !!productId,
  })
}

export function useUploadProductModel(productId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { file: File; changeLog?: string }) =>
      uploadProductModel(productId, vars.file, vars.changeLog),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products', productId] })
      qc.invalidateQueries({ queryKey: ['products', productId, 'versions'] })
    },
  })
}
