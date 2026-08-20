import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { OrderState } from '@/lib/status'
import {
  fetchOrder,
  fetchOrders,
  placeOrder,
  recordPayment,
  transitionOrder,
  type PlaceOrderItem,
} from '@/lib/orders-api'

export function useOrders(status?: string) {
  return useQuery({
    queryKey: ['orders', { status }],
    queryFn: () => fetchOrders(status ? { status } : undefined),
  })
}

export function useOrder(id: number) {
  return useQuery({ queryKey: ['orders', id], queryFn: () => fetchOrder(id), enabled: !!id })
}

export function usePlaceOrder() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { items: PlaceOrderItem[]; deliveryAddress?: string; notes?: string }) =>
      placeOrder(vars.items, vars.deliveryAddress, vars.notes),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['orders'] }),
  })
}

export function useTransition(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { to: OrderState; note?: string }) => transitionOrder(orderId, vars.to, vars.note),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['orders', orderId] })
      qc.invalidateQueries({ queryKey: ['orders'] })
    },
  })
}

export function useRecordPayment(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { amount: number; method?: string; note?: string }) =>
      recordPayment(orderId, vars.amount, vars.method, vars.note),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['orders', orderId] }),
  })
}
