import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { deleteUser, fetchUsers, updateUser } from '@/lib/users-api'

export function useUsers() {
  return useQuery({ queryKey: ['users'], queryFn: fetchUsers })
}

function useInvalidateUsers() {
  const qc = useQueryClient()
  return () => qc.invalidateQueries({ queryKey: ['users'] })
}

export function useUpdateUser() {
  const invalidate = useInvalidateUsers()
  return useMutation({
    mutationFn: (vars: { id: number; name?: string; is_active?: boolean; role?: string }) =>
      updateUser(vars.id, { name: vars.name, is_active: vars.is_active, role: vars.role }),
    onSuccess: invalidate,
  })
}

export function useDeleteUser() {
  const invalidate = useInvalidateUsers()
  return useMutation({ mutationFn: (id: number) => deleteUser(id), onSuccess: invalidate })
}
