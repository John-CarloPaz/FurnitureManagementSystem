import { api } from './api'

export type StageName = 'cutting' | 'assembly' | 'sanding' | 'finishing' | 'qc'
export type StageStatus = 'pending' | 'in_progress' | 'done' | 'blocked'

export interface Stage {
  id: number
  stage: StageName
  stage_label: string
  status: StageStatus
  status_label: string
  operator_id: number | null
  expected_minutes: number | null
  started_at: string | null
  ended_at: string | null
  is_delayed: boolean
  qc_passed: boolean | null
  notes: string | null
}

export interface ProductionItem {
  id: number
  product_name: string
  quantity: number
  percent: number
  stages: Stage[]
}

export interface ShopFloorOrder {
  id: number
  order_number: string
  status: string
  status_label: string
  customer: string | null
  has_delay: boolean
  items: ProductionItem[]
}

export async function fetchShopFloor(): Promise<ShopFloorOrder[]> {
  const { data } = await api.get('/shop-floor')
  return data.data
}

export async function fetchProduction(orderId: number): Promise<ShopFloorOrder> {
  const { data } = await api.get(`/orders/${orderId}/production`)
  return data.data
}

export async function startStage(itemId: number, stage: StageName): Promise<Stage> {
  const { data } = await api.post(`/order-items/${itemId}/stages/${stage}/start`)
  return data.data
}

export async function completeStage(
  itemId: number,
  stage: StageName,
  qcPassed?: boolean,
  notes?: string,
): Promise<Stage> {
  const { data } = await api.post(`/order-items/${itemId}/stages/${stage}/complete`, {
    qc_passed: qcPassed,
    notes,
  })
  return data.data
}
