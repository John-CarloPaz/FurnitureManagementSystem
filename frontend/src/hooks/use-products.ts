import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  changeProductStatus,
  createProduct,
  fetchCatalogOptions,
  fetchProduct,
  fetchProductVersions,
  fetchProducts,
  fetchProductInsights,
  generateProductModel,
  generateVariationModel,
  syncProductVariations,
  updateProduct,
  uploadProductModel,
  uploadVariationModel,
  type ProductInput,
  type VariationInput,
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
    // Poll while any 3D model (base or a variation) is generating so results appear when ready.
    refetchInterval: (query) => {
      const busy = (s?: string) => s === 'pending' || s === 'processing'
      const d = query.state.data
      const anyBusy = busy(d?.model_generation?.status) || (d?.variations ?? []).some((v) => busy(v.generation?.status))
      return anyBusy ? 4000 : false
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

export function useProductInsights(productId: number) {
  return useQuery({
    queryKey: ['products', productId, 'insights'],
    queryFn: () => fetchProductInsights(productId),
    enabled: !!productId,
  })
}

export function useSyncVariations(productId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (variations: VariationInput[]) => syncProductVariations(productId, variations),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products', productId] }),
  })
}

export function useUploadVariationModel(productId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { variationId: number; file: File }) => uploadVariationModel(productId, vars.variationId, vars.file),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products', productId] }),
  })
}

export function useGenerateVariationModel(productId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { variationId: number; image: File }) => generateVariationModel(productId, vars.variationId, vars.image),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['products', productId] }),
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
