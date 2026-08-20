import { api } from './api'
import type { OrderState } from './status'
import type { Paginated } from './products-api'

export interface OrderItem {
  id: number
  product_id: number | null
  product_name: string
  unit_price: string
  quantity: number
  line_total: string
}

export interface OrderPayment {
  id: number
  amount: string
  method: string | null
  note: string | null
  recorded_by: number | null
  created_at: string
}

export interface OrderTransition {
  id: number
  from_state: OrderState | null
  to_state: OrderState
  to_label: string
  note: string | null
  actor?: string | null
  created_at: string
}

export interface Order {
  id: number
  order_number: string
  status: OrderState
  status_label: string
  allowed_transitions: OrderState[]
  customer_id: number
  customer?: { id: number; name: string; email: string }
  subtotal: string
  delivery_fee: string
  total: string
  downpayment: string
  amount_paid: string
  payment_status: string
  delivery_address: string | null
  notes: string | null
  items?: OrderItem[]
  payments?: OrderPayment[]
  transitions?: OrderTransition[]
  placed_at: string | null
  confirmed_at: string | null
  delivered_at: string | null
  created_at: string
  updated_at: string
}

export interface PlaceOrderItem {
  product_id: number
  quantity: number
}

export async function fetchOrders(params?: { status?: string }): Promise<Paginated<Order>> {
  const { data } = await api.get('/orders', { params })
  return data
}

export async function fetchOrder(id: number): Promise<Order> {
  const { data } = await api.get(`/orders/${id}`)
  return data.data
}

export async function placeOrder(
  items: PlaceOrderItem[],
  deliveryAddress?: string,
  notes?: string,
): Promise<Order> {
  const { data } = await api.post('/orders', { items, delivery_address: deliveryAddress, notes })
  return data.data
}

export async function transitionOrder(id: number, to: OrderState, note?: string): Promise<Order> {
  const { data } = await api.post(`/orders/${id}/transition`, { to, note })
  return data.data
}

export async function recordPayment(id: number, amount: number, method?: string, note?: string): Promise<Order> {
  const { data } = await api.post(`/orders/${id}/payments`, { amount, method, note })
  return data.data
}
