import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createInvitation, fetchInvitations, revokeInvitation } from '@/lib/invitations-api'

export function useInvitations(enabled = true) {
  return useQuery({ queryKey: ['invitations'], queryFn: fetchInvitations, enabled })
}

export function useCreateInvitation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { email: string; role: string }) => createInvitation(payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['invitations'] }),
  })
}

export function useRevokeInvitation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => revokeInvitation(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['invitations'] }),
  })
}
