import { api } from './api'

export interface StageStat {
  stage: string
  delayed: number
  avg_minutes: number
}

export interface Bottleneck {
  stage: string
  delayed_count: number
  avg_minutes: number
  breakdown: StageStat[]
}

export interface KpiData {
  headline?: {
    ote: number | null
    avg_lead_time_days: number | null
    on_time_rate: number | null
    defect_rate: number
  }
  orders_by_status?: Record<string, number>
  bottleneck?: Bottleneck | null
  deliveries_by_status?: Record<string, number>
  ote?: number | null
  defect_rate?: number
  on_time_rate?: number | null
  avg_lead_time_days?: number | null
}

export async function fetchDashboard(): Promise<KpiData> {
  const { data } = await api.get('/kpi')
  return data.data
}

export async function fetchShopFloorKpi(): Promise<KpiData> {
  const { data } = await api.get('/kpi/shop-floor')
  return data.data
}

export async function fetchDeliveryKpi(): Promise<KpiData> {
  const { data } = await api.get('/kpi/delivery')
  return data.data
}
