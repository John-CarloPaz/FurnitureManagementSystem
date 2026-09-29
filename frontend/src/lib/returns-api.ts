import { api } from './api'

export const RETURN_REASONS = ['damaged', 'defective', 'wrong_item', 'not_as_described', 'changed_mind', 'other'] as const
export const RETURN_STATUSES = ['REQUESTED', 'APPROVED', 'REJECTED', 'REFUNDED'] as const

export interface ReturnRequest {
  id: number
  order_id: number
  order_number?: string
  reason: string
  description: string | null
  status: string
  resolution_note: string | null
  refund_amount: string | null
  requester?: string
  is_mine: boolean
  created_at: string
}

export const labelize = (s: string) => s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())

export async function fetchReturns(): Promise<ReturnRequest[]> {
  return (await api.get('/returns')).data.data
}

export async function createReturn(orderId: number, reason: string, description?: string): Promise<ReturnRequest> {
  return (await api.post(`/orders/${orderId}/returns`, { reason, description })).data.data
}

export async function updateReturn(id: number, payload: { status: string; resolution_note?: string; refund_amount?: number }): Promise<ReturnRequest> {
  return (await api.patch(`/returns/${id}`, payload)).data.data
}
