import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { getMe, login, logout } from '@/lib/auth-api'
import { tokenStore } from '@/lib/api'

/** Fetch the current user (only if a token exists). */
export function useMe() {
  return useQuery({
    queryKey: ['me'],
    queryFn: getMe,
    enabled: !!tokenStore.get(),
    retry: false,
    staleTime: Infinity,
  })
}

/** Auth state + permission helpers. */
export function useAuth() {
  const { data: user, isLoading } = useMe()
  const has = (perm: string) => !!user?.permissions.includes(perm)
  const hasAny = (perms: string[]) => perms.some(has)
  return { user, isLoading, has, hasAny, isAuthenticated: !!user }
}

export function useLogin() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { email: string; password: string }) => login(vars.email, vars.password),
    onSuccess: (res) => {
      tokenStore.set(res.token)
      qc.setQueryData(['me'], res.user)
    },
  })
}

export function useLogout() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: logout,
    onSuccess: () => qc.clear(),
  })
}
