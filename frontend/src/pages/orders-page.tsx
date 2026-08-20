import { useNavigate, Link } from 'react-router-dom'
import { Card } from '@/components/ui/card'
import { StatusPill } from '@/components/ui/status-pill'
import { useOrders } from '@/hooks/use-orders'
import { peso } from '@/lib/status'
import { cn } from '@/lib/utils'

const PAYMENT_COLOR: Record<string, string> = {
  UNPAID: 'var(--status-neutral)',
  PARTIAL: 'var(--status-warning)',
  PAID: 'var(--status-success)',
}

export function OrdersPage() {
  const navigate = useNavigate()
  const { data, isLoading } = useOrders()

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div>
        <h1 className="font-display text-3xl text-fg">Orders</h1>
        <p className="mt-1 text-muted">
          Track every order through production and delivery.{' '}
          <Link to="/catalog" className="text-walnut">Browse the catalog</Link> to place one.
        </p>
      </div>

      <Card className="p-0">
        {isLoading ? (
          <div className="p-8 text-center text-muted">Loading orders…</div>
        ) : !data?.data.length ? (
          <div className="p-8 text-center text-muted">No orders yet.</div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs uppercase tracking-wide text-muted">
                <th className="px-6 py-3 font-medium">Order</th>
                <th className="px-6 py-3 font-medium">Customer</th>
                <th className="px-6 py-3 font-medium">Total</th>
                <th className="px-6 py-3 font-medium">Payment</th>
                <th className="px-6 py-3 font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              {data.data.map((o) => (
                <tr
                  key={o.id}
                  onClick={() => navigate(`/orders/${o.id}`)}
                  className="cursor-pointer border-t border-border transition-colors hover:bg-surface-2"
                >
                  <td className="px-6 py-3 font-mono text-xs text-muted">{o.order_number}</td>
                  <td className="px-6 py-3 text-muted">{o.customer?.name ?? '—'}</td>
                  <td className="px-6 py-3 font-mono text-fg">{peso(o.total)}</td>
                  <td className="px-6 py-3">
                    <span className={cn('text-xs font-medium')} style={{ color: PAYMENT_COLOR[o.payment_status] }}>
                      {o.payment_status}
                    </span>
                  </td>
                  <td className="px-6 py-3"><StatusPill state={o.status} /></td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
    </div>
  )
}
