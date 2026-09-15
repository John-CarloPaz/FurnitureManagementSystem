import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  changeProductStatus,
  createProduct,
  fetchCatalogOptions,
  fetchProduct,
  fetchProductVersions,
  fetchProducts,
  generateProductModel,
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
  return useQuery({
    queryKey: ['products', id],
    queryFn: () => fetchProduct(id),
    enabled: !!id,
    // Poll while a 3D model is being generated so the viewer appears when it's ready.
    refetchInterval: (query) => {
      const status = query.state.data?.model_generation?.status
      return status === 'pending' || status === 'processing' ? 4000 : false
    },
  })
}

export function useCreateProduct() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { payload: ProductInput; image?: File }) => createProduct(vars.payload, vars.image),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products'] }),
  })
}

export function useGenerateProductModel(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (image?: File) => generateProductModel(id, image),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products', id] }),
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
