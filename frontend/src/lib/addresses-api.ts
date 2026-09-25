import { api } from './api'

export interface DeliveryAddress {
  id: number
  label: string | null
  country: string
  province_code: string | null
  province_name: string
  city_code: string | null
  city_name: string
  barangay_code: string | null
  barangay_name: string
  street: string
  landmark: string | null
  notes: string | null
  is_default: boolean
  formatted: string
}

export interface AddressInput {
  label?: string | null
  country: string
  province_code?: string | null
  province_name: string
  city_code?: string | null
  city_name: string
  barangay_code?: string | null
  barangay_name: string
  street: string
  landmark?: string | null
  notes?: string | null
  is_default?: boolean
}

export async function fetchAddresses(): Promise<DeliveryAddress[]> {
  return (await api.get('/addresses')).data.data
}

export async function createAddress(payload: AddressInput): Promise<DeliveryAddress> {
  return (await api.post('/addresses', payload)).data.data
}

export async function updateAddress(id: number, payload: AddressInput): Promise<DeliveryAddress> {
  return (await api.patch(`/addresses/${id}`, payload)).data.data
}

export async function deleteAddress(id: number): Promise<void> {
  await api.delete(`/addresses/${id}`)
}
