import { useNavigate } from 'react-router-dom'
import { TrendingUp, Clock, CheckCircle2, AlertTriangle } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { StatusPill } from '@/components/ui/status-pill'
import { DistributionBars } from '@/components/charts/distribution'
import { useAuth } from '@/hooks/use-auth'
import { useKpi } from '@/hooks/use-kpi'
import { useOrders } from '@/hooks/use-orders'
import { STATUS_META, peso, type OrderState } from '@/lib/status'

const pct = (v: number | null | undefined) => (v == null ? '—' : `${v}%`)
const days = (v: number | null | undefined) => (v == null ? '—' : `${v}d`)

export function DashboardPage() {
  const { user, has } = useAuth()
  const navigate = useNavigate()
  const greeting = user?.username || user?.name?.split(' ')[0] || 'there'

  const canKpi = has('kpi.view') || has('kpi.view.shopfloor') || has('kpi.view.delivery')
  const { query: kpi } = useKpi()
  const orders = useOrders()

  const h = kpi.data?.headline
  const kpiCards = [
    { label: 'On-Time Delivery', value: pct(h?.on_time_rate ?? kpi.data?.on_time_rate), icon: CheckCircle2, tone: 'var(--status-success)' },
    { label: 'Avg Lead Time', value: days(h?.avg_lead_time_days ?? kpi.data?.avg_lead_time_days), icon: Clock, tone: 'var(--status-active)' },
    { label: 'OTE', value: pct(h?.ote ?? kpi.data?.ote), icon: TrendingUp, tone: 'var(--status-info)' },
    { label: 'Defect / Rework', value: pct(h?.defect_rate ?? kpi.data?.defect_rate), icon: AlertTriangle, tone: 'var(--status-alert)' },
  ]

  const distribution = Object.entries(kpi.data?.orders_by_status ?? {}).map(([s, c]) => ({
    label: STATUS_META[s as OrderState]?.label ?? s,
    value: c,
    color: STATUS_META[s as OrderState]?.color ?? 'var(--walnut)',
  }))

  const recent = orders.data?.data.slice(0, 6) ?? []

  return (
    <div className="mx-auto max-w-6xl space-y-8">
      <div>
        <h1 className="font-display text-3xl text-fg">Welcome back, {greeting}</h1>
        <p className="mt-1 text-muted">Here's what's happening on the floor today.</p>
      </div>

      {canKpi && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {kpiCards.map(({ label, value, icon: Icon, tone }) => (
            <Card key={label} className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-sm text-muted">{label}</span>
                <Icon size={18} style={{ color: tone }} />
              </div>
              <span className="font-display text-3xl text-fg">{kpi.isLoading ? '…' : value}</span>
            </Card>
          ))}
        </div>
      )}

      <div className={`grid gap-6 ${canKpi ? 'lg:grid-cols-5' : ''}`}>
        {canKpi && distribution.length > 0 && (
          <Card className="space-y-4 lg:col-span-2">
            <h2 className="font-display text-lg text-fg">Orders by stage</h2>
            <DistributionBars items={distribution} />
          </Card>
        )}

        <Card className={`p-0 ${canKpi ? 'lg:col-span-3' : ''}`}>
          <div className="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 className="font-display text-lg text-fg">Recent Orders</h2>
            <button onClick={() => navigate('/orders')} className="text-sm text-walnut hover:underline">View all</button>
          </div>
          {orders.isLoading ? (
            <p className="p-8 text-center text-muted">Loading orders…</p>
          ) : !recent.length ? (
            <p className="p-8 text-center text-muted">No orders yet.</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-xs uppercase tracking-wide text-muted">
                  <th className="px-6 py-3 font-medium">Order</th>
                  <th className="px-6 py-3 font-medium">Customer</th>
                  <th className="px-6 py-3 font-medium">Total</th>
                  <th className="px-6 py-3 font-medium">Status</th>
                </tr>
              </thead>
              <tbody>
                {recent.map((o) => (
                  <tr
                    key={o.id}
                    onClick={() => navigate(`/orders/${o.id}`)}
                    className="cursor-pointer border-t border-border transition-colors hover:bg-surface-2"
                  >
                    <td className="px-6 py-3 font-mono text-xs text-muted">{o.order_number}</td>
                    <td className="px-6 py-3 text-muted">{o.customer?.name ?? '—'}</td>
                    <td className="px-6 py-3 font-mono text-fg">{peso(o.total)}</td>
                    <td className="px-6 py-3"><StatusPill state={o.status} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </Card>
      </div>
    </div>
  )
}
