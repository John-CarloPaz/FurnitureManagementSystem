import { useQuery } from '@tanstack/react-query'
import { useAuth } from '@/hooks/use-auth'
import { fetchDashboard, fetchDeliveryKpi, fetchShopFloorKpi } from '@/lib/kpi-api'

/** Fetches the richest KPI endpoint the current role can see. */
export function useKpi() {
  const { has } = useAuth()
  const scope = has('kpi.view')
    ? 'full'
    : has('kpi.view.shopfloor')
      ? 'shop'
      : has('kpi.view.delivery')
        ? 'delivery'
        : 'none'

  const query = useQuery({
    queryKey: ['kpi', scope],
    enabled: scope !== 'none',
    refetchInterval: 60_000,
    queryFn: () =>
      scope === 'full' ? fetchDashboard() : scope === 'shop' ? fetchShopFloorKpi() : fetchDeliveryKpi(),
  })

  return { scope, query }
}
