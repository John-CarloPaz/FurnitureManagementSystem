import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  assignDelivery,
  dispatchDelivery,
  fetchDeliveries,
  fetchDrivers,
  fetchUnassignedOrders,
  logLocation,
  recordProof,
} from '@/lib/delivery-api'

export function useDeliveries() {
  return useQuery({ queryKey: ['deliveries'], queryFn: fetchDeliveries, refetchInterval: 15_000 })
}

export function useUnassignedOrders(enabled: boolean) {
  return useQuery({ queryKey: ['deliveries', 'unassigned'], queryFn: fetchUnassignedOrders, enabled })
}

export function useDrivers(enabled: boolean) {
  return useQuery({ queryKey: ['deliveries', 'drivers'], queryFn: fetchDrivers, enabled, staleTime: 60_000 })
}

function useInvalidate() {
  const qc = useQueryClient()
  return () => {
    qc.invalidateQueries({ queryKey: ['deliveries'] })
    qc.invalidateQueries({ queryKey: ['orders'] })
  }
}

export function useAssignDelivery() {
  const invalidate = useInvalidate()
  return useMutation({
    mutationFn: (vars: { orderId: number; driver_id?: number; batch_label?: string }) =>
      assignDelivery(vars.orderId, { driver_id: vars.driver_id, batch_label: vars.batch_label }),
    onSuccess: invalidate,
  })
}

export function useDispatchDelivery() {
  const invalidate = useInvalidate()
  return useMutation({
    mutationFn: (vars: { id: number; manual_location?: string }) => dispatchDelivery(vars.id, { manual_location: vars.manual_location }),
    onSuccess: invalidate,
  })
}

export function useLogLocation() {
  const invalidate = useInvalidate()
  return useMutation({
    mutationFn: (vars: { id: number; manual_location?: string; note?: string }) =>
      logLocation(vars.id, { manual_location: vars.manual_location, note: vars.note }),
    onSuccess: invalidate,
  })
}

export function useRecordProof() {
  const invalidate = useInvalidate()
  return useMutation({
    mutationFn: (vars: { id: number; form: FormData }) => recordProof(vars.id, vars.form),
    onSuccess: invalidate,
  })
}
