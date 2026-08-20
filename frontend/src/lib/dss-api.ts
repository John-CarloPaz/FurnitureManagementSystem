import { api } from './api'
import type { Bottleneck } from './kpi-api'

export interface SchedulePlanItem {
  sequence: number
  order_item_id: number
  order_number: string
  product_name: string
  due_at: string
  lead_days: number
}

export interface Schedule {
  strategy: string
  plan: SchedulePlanItem[]
}

export interface BottleneckAdvice {
  bottleneck: Bottleneck | null
  recommendation: string
}

export async function fetchSchedule(): Promise<Schedule> {
  const { data } = await api.post('/dss/schedule')
  return data.data
}

export async function fetchBottleneckAdvice(): Promise<BottleneckAdvice> {
  const { data } = await api.get('/dss/bottlenecks')
  return data.data
}
