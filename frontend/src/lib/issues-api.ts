import { api } from './api'

export const ISSUE_CATEGORIES = ['delivery_problem', 'damaged_item', 'missing_item', 'wrong_item', 'product_quality', 'other'] as const
export const ISSUE_STATUSES = ['OPEN', 'IN_REVIEW', 'RESOLVED'] as const

export interface IssueReport {
  id: number
  order_id: number
  order_number?: string
  category: string
  description: string
  status: string
  resolution_note: string | null
  reporter?: string
  is_mine: boolean
  created_at: string
}

export async function fetchIssues(): Promise<IssueReport[]> {
  return (await api.get('/issues')).data.data
}

export async function createIssue(orderId: number, category: string, description: string): Promise<IssueReport> {
  return (await api.post(`/orders/${orderId}/issues`, { category, description })).data.data
}

export async function updateIssue(id: number, payload: { status: string; resolution_note?: string }): Promise<IssueReport> {
  return (await api.patch(`/issues/${id}`, payload)).data.data
}
