import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createVoucher,
  deleteVoucher,
  fetchVouchers,
  updateVoucher,
  type VoucherInput,
} from '@/lib/vouchers-api'

export function useVouchers() {
  return useQuery({ queryKey: ['vouchers'], queryFn: fetchVouchers })
}

export function useCreateVoucher() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: VoucherInput) => createVoucher(payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vouchers'] }),
  })
}

export function useUpdateVoucher() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { id: number; payload: VoucherInput }) => updateVoucher(vars.id, vars.payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vouchers'] }),
  })
}

export function useDeleteVoucher() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteVoucher(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vouchers'] }),
  })
}
