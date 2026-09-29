import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createIssue, fetchIssues, updateIssue } from '@/lib/issues-api'

export function useIssues() {
  return useQuery({ queryKey: ['issues'], queryFn: fetchIssues })
}

export function useCreateIssue(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { category: string; description: string }) => createIssue(orderId, vars.category, vars.description),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['orders', orderId] })
      qc.invalidateQueries({ queryKey: ['issues'] })
    },
  })
}

export function useUpdateIssue() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { id: number; status: string; resolution_note?: string }) =>
      updateIssue(vars.id, { status: vars.status, resolution_note: vars.resolution_note }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['issues'] }),
  })
}
