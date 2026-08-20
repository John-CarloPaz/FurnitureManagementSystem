import { api } from './api'
import type { Order } from './orders-api'

export interface DeliveryEvent {
  id: number
  type: string
  type_label: string
  lat: string | null
  lng: string | null
  manual_location: string | null
  note: string | null
  created_by?: string | null
  created_at: string
}

export interface DeliveryProof {
  id: number
  recipient_name: string | null
  delivered_at: string
  photo_url: string
}

export interface DeliveryAssignment {
  id: number
  order_id: number
  order?: { id: number; order_number: string; status: string; customer: string | null }
  driver?: string | null
  driver_id: number | null
  coordinator?: string | null
  batch_label: string | null
  status: string
  status_label: string
  assigned_at: string | null
  events?: DeliveryEvent[]
  proof?: DeliveryProof | null
  created_at: string
}

export interface Driver {
  id: number
  name: string
}

export async function fetchDeliveries(): Promise<DeliveryAssignment[]> {
  const { data } = await api.get('/deliveries')
  return data.data
}

export async function fetchUnassignedOrders(): Promise<Order[]> {
  const { data } = await api.get('/deliveries/unassigned')
  return data.data
}

export async function fetchDrivers(): Promise<Driver[]> {
  const { data } = await api.get('/deliveries/drivers')
  return data.data
}

export async function assignDelivery(orderId: number, payload: { driver_id?: number; batch_label?: string }): Promise<DeliveryAssignment> {
  const { data } = await api.post(`/orders/${orderId}/delivery`, payload)
  return data.data
}

export async function dispatchDelivery(id: number, payload: { manual_location?: string }): Promise<DeliveryAssignment> {
  const { data } = await api.post(`/deliveries/${id}/dispatch`, payload)
  return data.data
}

export async function logLocation(id: number, payload: { manual_location?: string; note?: string }): Promise<DeliveryAssignment> {
  const { data } = await api.post(`/deliveries/${id}/location`, payload)
  return data.data
}

export async function recordProof(id: number, form: FormData): Promise<DeliveryAssignment> {
  const { data } = await api.post(`/deliveries/${id}/proof`, form)
  return data.data
}
