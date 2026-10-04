import { api } from './api'
import type { User } from './auth-api'

export async function fetchUsers(role?: string): Promise<User[]> {
  const { data } = await api.get('/users', { params: role ? { role } : undefined })
  return data.data
}

export async function updateUser(
  id: number,
  payload: { name?: string; is_active?: boolean; role?: string },
): Promise<User> {
  const { data } = await api.patch(`/users/${id}`, payload)
  return data.data
}

export async function deleteUser(id: number): Promise<void> {
  await api.delete(`/users/${id}`)
}

export async function transferOwnership(userId: number): Promise<User> {
  const { data } = await api.post('/owner/transfer', { user_id: userId })
  return data.data
}
