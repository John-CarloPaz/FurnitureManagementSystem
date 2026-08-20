import { Navigate, Outlet } from 'react-router-dom'
import { tokenStore } from '@/lib/api'
import { useMe } from '@/hooks/use-auth'

/** Gate for authenticated routes: redirects to /login when unauthenticated. */
export function ProtectedRoute() {
  const hasToken = !!tokenStore.get()
  const { data: user, isLoading, isError } = useMe()

  if (!hasToken) return <Navigate to="/login" replace />
  if (isLoading) {
    return <div className="flex h-screen items-center justify-center text-muted">Loading…</div>
  }
  if (isError || !user) return <Navigate to="/login" replace />

  return <Outlet />
}
