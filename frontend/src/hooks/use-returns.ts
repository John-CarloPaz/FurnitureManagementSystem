import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createReturn, fetchReturns, updateReturn } from '@/lib/returns-api'

export function useReturns() {
  return useQuery({ queryKey: ['returns'], queryFn: fetchReturns })
}

export function useCreateReturn(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { reason: string; description?: string }) => createReturn(orderId, vars.reason, vars.description),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['orders', orderId] })
      qc.invalidateQueries({ queryKey: ['returns'] })
    },
  })
}

export function useUpdateReturn() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { id: number; status: string; resolution_note?: string; refund_amount?: number }) =>
      updateReturn(vars.id, { status: vars.status, resolution_note: vars.resolution_note, refund_amount: vars.refund_amount }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['returns'] }),
  })
}
