import { Card } from '@/components/ui/card'
import { StatusPill } from '@/components/ui/status-pill'
import type { OrderState } from '@/lib/status'
import { TrendingUp, Clock, CheckCircle2, AlertTriangle } from 'lucide-react'
import { useAuth } from '@/hooks/use-auth'

const KPIS = [
  { label: 'On-Time Delivery', value: '94%', delta: '+3%', icon: CheckCircle2, tone: 'var(--status-success)' },
  { label: 'Avg Lead Time', value: '6.2d', delta: '-0.4d', icon: Clock, tone: 'var(--status-active)' },
  { label: 'OTE', value: '81%', delta: '+2%', icon: TrendingUp, tone: 'var(--status-info)' },
  { label: 'Defect / Rework', value: '3.1%', delta: '-0.6%', icon: AlertTriangle, tone: 'var(--status-alert)' },
]

const ORDERS: { id: string; title: string; customer: string; state: OrderState }[] = [
  { id: 'ORD-2041', title: 'Oak Dining Table', customer: 'M. Santos', state: 'IN_PRODUCTION' },
  { id: 'ORD-2040', title: 'Walnut Bookshelf', customer: 'J. Reyes', state: 'QUALITY_CHECK' },
  { id: 'ORD-2039', title: 'Leather Armchair', customer: 'A. Cruz', state: 'PLACED' },
  { id: 'ORD-2038', title: 'Teak Bed Frame', customer: 'L. Garcia', state: 'OUT_FOR_DELIVERY' },
  { id: 'ORD-2037', title: 'Pine Wardrobe', customer: 'R. Tan', state: 'DELIVERED' },
]

export function DashboardPage() {
  const { user } = useAuth()
  const firstName = user?.name.split(' ')[0] ?? 'there'
  return (
    <div className="mx-auto max-w-6xl space-y-8">
      <div>
        <h1 className="font-display text-3xl text-fg">Welcome back, {firstName}</h1>
        <p className="mt-1 text-muted">Here's what's happening on the floor today.</p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {KPIS.map(({ label, value, delta, icon: Icon, tone }) => (
          <Card key={label} className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="text-sm text-muted">{label}</span>
              <Icon size={18} style={{ color: tone }} />
            </div>
            <div className="flex items-baseline gap-2">
              <span className="font-display text-3xl text-fg">{value}</span>
              <span className="text-xs font-medium" style={{ color: tone }}>
                {delta}
              </span>
            </div>
          </Card>
        ))}
      </div>

      <Card className="p-0">
        <div className="flex items-center justify-between border-b border-border px-6 py-4">
          <h2 className="font-display text-lg text-fg">Recent Orders</h2>
          <span className="text-sm text-muted">Live</span>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs uppercase tracking-wide text-muted">
              <th className="px-6 py-3 font-medium">Order</th>
              <th className="px-6 py-3 font-medium">Item</th>
              <th className="px-6 py-3 font-medium">Customer</th>
              <th className="px-6 py-3 font-medium">Status</th>
            </tr>
          </thead>
          <tbody>
            {ORDERS.map((o) => (
              <tr key={o.id} className="border-t border-border transition-colors hover:bg-surface-2">
                <td className="px-6 py-3 font-mono text-xs text-muted">{o.id}</td>
                <td className="px-6 py-3 text-fg">{o.title}</td>
                <td className="px-6 py-3 text-muted">{o.customer}</td>
                <td className="px-6 py-3">
                  <StatusPill state={o.state} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </div>
  )
}
