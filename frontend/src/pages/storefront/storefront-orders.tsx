import { useNavigate, Link } from 'react-router-dom'
import { Card } from '@/components/ui/card'
import { StatusPill } from '@/components/ui/status-pill'
import { useOrders } from '@/hooks/use-orders'
import { useAuth } from '@/hooks/use-auth'
import { peso } from '@/lib/status'

export function StorefrontOrders() {
  const navigate = useNavigate()
  const { isAuthenticated } = useAuth()
  const { data, isLoading } = useOrders()

  if (!isAuthenticated) {
    return (
      <div className="mx-auto max-w-2xl">
        <Card className="space-y-3 text-center text-muted">
          <p>Sign in to see and track your orders.</p>
          <Link to="/login" className="inline-block rounded-full bg-walnut px-4 py-1.5 text-sm font-medium text-walnut-foreground hover:bg-walnut-hover">Sign in</Link>
        </Card>
      </div>
    )
  }

  const orders = data?.data ?? []

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <h1 className="font-display text-3xl text-fg">My orders</h1>
      {isLoading ? (
        <p className="py-10 text-center text-muted">Loading…</p>
      ) : !orders.length ? (
        <Card className="text-center text-muted">No orders yet. <Link to="/shop" className="text-walnut">Start shopping</Link>.</Card>
      ) : (
        <Card className="p-0">
          <table className="w-full text-sm">
            <tbody>
              {orders.map((o) => (
                <tr key={o.id} onClick={() => navigate(`/shop/orders/${o.id}`)} className="cursor-pointer border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                  <td className="px-6 py-4">
                    <p className="font-mono text-xs text-muted">{o.order_number}</p>
                    <p className="text-fg">{peso(o.total)}</p>
                  </td>
                  <td className="px-6 py-4 text-right"><StatusPill state={o.status} /></td>
                </tr>
              ))}
            </tbody>
          </table>
        </Card>
      )}
    </div>
  )
}
