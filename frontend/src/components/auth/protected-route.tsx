import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { tokenStore } from '@/lib/api'
import { useMe } from '@/hooks/use-auth'

/**
 * Gate for the CRM (staff) routes.
 * - Guests at the site root land on the marketplace; deeper CRM links go to login (with ?next).
 * - Signed-in customers have no CRM workspace, so they're sent to the storefront too.
 */
export function ProtectedRoute() {
  const hasToken = !!tokenStore.get()
  const location = useLocation()
  const { data: user, isLoading, isError } = useMe()

  if (!hasToken) {
    if (location.pathname === '/') return <Navigate to="/shop" replace />
    const next = encodeURIComponent(location.pathname + location.search)
    return <Navigate to={`/login?next=${next}`} replace />
  }
  if (isLoading) {
    return <div className="flex h-screen items-center justify-center text-muted">Loading…</div>
  }
  if (isError || !user) return <Navigate to="/login" replace />

  const isStaff = user.roles.some((r) => r !== 'customer')
  if (!isStaff) return <Navigate to="/shop" replace />

  return <Outlet />
}
