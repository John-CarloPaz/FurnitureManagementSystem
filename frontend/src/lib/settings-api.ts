import { api } from './api'

export interface ShopConfig {
  shipping_fee: number
  vat_rate: number
}

export async function fetchAdminSettings(): Promise<ShopConfig> {
  return (await api.get('/settings')).data.data
}

export async function updateAdminSettings(payload: ShopConfig): Promise<ShopConfig> {
  return (await api.patch('/settings', payload)).data.data
}
