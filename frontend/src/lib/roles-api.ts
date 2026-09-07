import { api } from './api'

/** "delivery_personnel" → "Delivery Personnel" for display. */
export function prettyRole(name: string): string {
  return name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

export interface Role {
  id: number
  name: string
  is_system: boolean
  permissions: string[]
  users_count: number
}

export type CrudAction = 'create' | 'read' | 'update' | 'delete'

export interface Ability {
  name: string
  label: string
  type: 'crud' | 'action'
  action?: CrudAction
}

export interface PermissionGroup {
  key: string
  label: string
  abilities: Ability[]
}

export async function fetchRoles(): Promise<Role[]> {
  const { data } = await api.get('/roles')
  return data.data
}

export async function fetchPermissionCatalog(): Promise<PermissionGroup[]> {
  const { data } = await api.get('/permissions')
  return data.data
}

export async function createRole(payload: { name: string; permissions: string[] }): Promise<Role> {
  const { data } = await api.post('/roles', payload)
  return data.data
}

export async function updateRole(id: number, payload: { name?: string; permissions: string[] }): Promise<Role> {
  const { data } = await api.patch(`/roles/${id}`, payload)
  return data.data
}

export async function deleteRole(id: number): Promise<void> {
  await api.delete(`/roles/${id}`)
}
