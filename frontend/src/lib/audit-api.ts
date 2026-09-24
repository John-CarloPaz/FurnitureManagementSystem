import { api } from './api'

export type AuditEvent = 'created' | 'updated' | 'deleted'

export interface AuditLog {
  id: number
  user: string | null
  event: AuditEvent
  method: string | null
  path: string | null
  entity: string
  entity_id: number | null
  // updated → { field: { old, new } }; created → { field: value }
  changes: Record<string, unknown> | null
  ip_address: string | null
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number }
}

export async function fetchAuditLogs(params?: {
  page?: number
  entity?: string
  event?: AuditEvent
}): Promise<Paginated<AuditLog>> {
  const { data } = await api.get('/audit-logs', { params })
  return data
}

export async function exportAuditLogs(params?: { entity?: string; event?: AuditEvent }): Promise<Blob> {
  const { data } = await api.get('/audit-logs/export', { params, responseType: 'blob' })
  return data
}
