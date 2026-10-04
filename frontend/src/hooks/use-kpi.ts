import { useEffect } from 'react'
import { keepPreviousData, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/hooks/use-auth'
import { fetchDashboard, fetchDeliveryKpi, fetchShopFloorKpi } from '@/lib/kpi-api'

type KpiScope = 'full' | 'shop' | 'delivery' | 'none'

/** The richest KPI endpoint the current role may read. */
function useKpiScope(): KpiScope {
  const { has } = useAuth()
  return has('kpi.view')
    ? 'full'
    : has('kpi.view.shopfloor')
      ? 'shop'
      : has('kpi.view.delivery')
        ? 'delivery'
        : 'none'
}

const queryKey = (scope: KpiScope) => ['kpi', scope] as const

const queryFn = (scope: KpiScope) =>
  scope === 'full' ? fetchDashboard() : scope === 'shop' ? fetchShopFloorKpi() : fetchDeliveryKpi()

// KPIs are expensive to compute, so cache them generously: fresh for 2 min, kept in
// memory for 30 so moving between Dashboard, Analytics and Reports is instant.
const STALE = 2 * 60_000
const GC = 30 * 60_000

/** Fetches the KPI snapshot, shared across Dashboard / Analytics / Reports (one cache key). */
export function useKpi() {
  const scope = useKpiScope()

  const query = useQuery({
    queryKey: queryKey(scope),
    enabled: scope !== 'none',
    staleTime: STALE,
    gcTime: GC,
    placeholderData: keepPreviousData,
    refetchInterval: 60_000,
    queryFn: () => queryFn(scope),
  })

  return { scope, query }
}

/** Warm the KPI cache once when the CRM loads, so the first open of Analytics/Reports is instant. */
export function usePrefetchKpi() {
  const scope = useKpiScope()
  const qc = useQueryClient()

  useEffect(() => {
    if (scope === 'none') return
    void qc.prefetchQuery({ queryKey: queryKey(scope), queryFn: () => queryFn(scope), staleTime: STALE, gcTime: GC })
  }, [scope, qc])
}
