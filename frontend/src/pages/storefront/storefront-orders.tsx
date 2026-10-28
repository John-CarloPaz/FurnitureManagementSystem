import { useNavigate, Link } from 'react-router-dom'
import { Check } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { StatusPill } from '@/components/ui/status-pill'
import { useOrders } from '@/hooks/use-orders'
import { useAuth } from '@/hooks/use-auth'
import { peso, orderProgress, isOrderDone } from '@/lib/status'

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
  const doneCount = orders.filter((o) => isOrderDone(o.status)).length

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-2">
        <h1 className="font-display text-3xl text-fg">My orders</h1>
        {orders.length > 0 && (
          <span className="inline-flex items-center gap-1.5 rounded-full border border-border px-3 py-1 text-sm text-muted">
            <Check size={14} className="text-[var(--status-success,#3f7d4a)]" />
            {doneCount} of {orders.length} delivered
          </span>
        )}
      </div>
      {isLoading ? (
        <p className="py-10 text-center text-muted">Loading…</p>
      ) : !orders.length ? (
        <Card className="text-center text-muted">No orders yet. <Link to="/shop" className="text-walnut">Start shopping</Link>.</Card>
      ) : (
        <Card className="p-0">
          <table className="w-full text-sm">
            <tbody>
              {orders.map((o) => {
                const pct = orderProgress(o.status)
                const done = isOrderDone(o.status)
                const cancelled = o.status === 'CANCELLED'
                const tone = done ? 'var(--status-success, #3f7d4a)' : cancelled ? 'var(--status-danger)' : 'var(--walnut)'
                return (
                  <tr key={o.id} onClick={() => navigate(`/shop/orders/${o.id}`)} className="cursor-pointer border-b border-border transition-colors last:border-0 hover:bg-surface-2">
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <p className="font-mono text-xs text-muted">{o.order_number}</p>
                        {done && (
                          <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium" style={{ backgroundColor: 'color-mix(in srgb, var(--status-success, #3f7d4a) 16%, transparent)', color: 'var(--status-success, #3f7d4a)' }}>
                            <Check size={11} /> Done
                          </span>
                        )}
                      </div>
                      <p className="text-fg">{peso(o.total)}</p>
                      <div className="mt-2 flex items-center gap-2">
                        <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-2">
                          <div className="h-full rounded-full transition-all" style={{ width: `${cancelled ? 0 : pct}%`, backgroundColor: tone }} />
                        </div>
                        <span className="w-9 text-right text-[11px] text-muted">{cancelled ? '—' : `${pct}%`}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-right align-top">
                      <div className="flex items-center justify-end gap-2">
                        {o.is_delayed && !done && (
                          <span className="rounded-full px-2 py-0.5 text-[10px] font-medium" style={{ backgroundColor: 'color-mix(in srgb, var(--status-alert, #b5731a) 14%, transparent)', color: 'var(--status-alert, #b5731a)' }}>Delayed</span>
                        )}
                        <StatusPill state={o.status} />
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </Card>
      )}
    </div>
  )
}
