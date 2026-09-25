import { useQuery } from '@tanstack/react-query'
import { fetchBarangays, fetchCities, fetchProvinces } from '@/lib/ph-address-api'

// PSGC reference data is effectively static, so cache it hard.
const STALE = 24 * 60 * 60 * 1000

export function useProvinces() {
  return useQuery({ queryKey: ['ph', 'provinces'], queryFn: fetchProvinces, staleTime: STALE })
}

export function useCities(province?: string) {
  return useQuery({
    queryKey: ['ph', 'cities', province],
    queryFn: () => fetchCities(province!),
    enabled: !!province,
    staleTime: STALE,
  })
}

export function useBarangays(city?: string) {
  return useQuery({
    queryKey: ['ph', 'barangays', city],
    queryFn: () => fetchBarangays(city!),
    enabled: !!city,
    staleTime: STALE,
  })
}
