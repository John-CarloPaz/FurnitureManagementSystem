import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createRole,
  deleteRole,
  fetchPermissionCatalog,
  fetchRoles,
  updateRole,
} from '@/lib/roles-api'

export function useRoles() {
  return useQuery({ queryKey: ['roles'], queryFn: fetchRoles })
}

export function usePermissionCatalog() {
  return useQuery({ queryKey: ['permissions'], queryFn: fetchPermissionCatalog, staleTime: Infinity })
}

function useInvalidateRoles() {
  const qc = useQueryClient()
  return () => qc.invalidateQueries({ queryKey: ['roles'] })
}

export function useCreateRole() {
  const invalidate = useInvalidateRoles()
  return useMutation({
    mutationFn: (payload: { name: string; permissions: string[] }) => createRole(payload),
    onSuccess: invalidate,
  })
}

export function useUpdateRole() {
  const invalidate = useInvalidateRoles()
  return useMutation({
    mutationFn: (vars: { id: number; name?: string; permissions: string[] }) =>
      updateRole(vars.id, { name: vars.name, permissions: vars.permissions }),
    onSuccess: invalidate,
  })
}

export function useDeleteRole() {
  const invalidate = useInvalidateRoles()
  return useMutation({ mutationFn: (id: number) => deleteRole(id), onSuccess: invalidate })
}
