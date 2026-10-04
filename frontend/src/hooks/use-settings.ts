import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { fetchAdminSettings, updateAdminSettings, type ShopConfig } from '@/lib/settings-api'

export function useAdminSettings() {
  return useQuery({ queryKey: ['admin-settings'], queryFn: fetchAdminSettings })
}

export function useUpdateSettings() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: ShopConfig) => updateAdminSettings(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-settings'] })
      qc.invalidateQueries({ queryKey: ['shop-settings'] }) // storefront checkout reads this
    },
  })
}
