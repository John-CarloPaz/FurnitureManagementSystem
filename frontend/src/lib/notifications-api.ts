import { api } from './api'

export interface AppNotification {
  id: string
  kind: string | null
  message: string
  data: Record<string, unknown>
  read_at: string | null
  created_at: string
}

export interface NotificationsResponse {
  data: AppNotification[]
  meta: { unread_count: number; current_page: number; last_page: number; total: number }
}

export async function fetchNotifications(): Promise<NotificationsResponse> {
  const { data } = await api.get('/notifications')
  return data
}

export async function markRead(id: string): Promise<void> {
  await api.post(`/notifications/${id}/read`)
}

export async function markAllRead(): Promise<void> {
  await api.post('/notifications/read-all')
}
