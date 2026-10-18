import { BarChart3 } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { DistributionBars } from '@/components/charts/distribution'
import { useProductInsights } from '@/hooks/use-products'
import { STATUS_META, peso, type OrderState } from '@/lib/status'

function Tile({ label, value }: { label: string; value: string | number }) {
  return (
    <div className="rounded-[var(--radius-sm)] border border-border p-3">
      <p className="text-xs text-muted">{label}</p>
      <p className="mt-0.5 font-display text-xl text-fg">{value}</p>
    </div>
  )
}

function Meter({ label, value, tone }: { label: string; value: number | null; tone: string }) {
  return (
    <div className="rounded-[var(--radius-sm)] border border-border p-3">
      <p className="text-xs text-muted">{label}</p>
      <p className="mt-0.5 font-display text-2xl text-fg">{value == null ? '—' : `${value}%`}</p>
      {value != null && (
        <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-surface-2">
          <div className="h-full rounded-full" style={{ width: `${Math.min(value, 100)}%`, backgroundColor: tone }} />
        </div>
      )}
    </div>
  )
}

export function ProductInsightsCard({ productId }: { productId: number }) {
  const { data: d, isLoading } = useProductInsights(productId)

  const distribution = Object.entries(d?.orders_by_status ?? {}).map(([status, count]) => ({
    label: STATUS_META[status as OrderState]?.label ?? status,
    value: count,
    color: STATUS_META[status as OrderState]?.color ?? 'var(--walnut)',
  }))

  return (
    <Card className="space-y-4">
      <h2 className="flex items-center gap-2 font-display text-lg text-fg"><BarChart3 size={18} /> Performance</h2>

      {isLoading || !d ? (
        <p className="py-6 text-center text-sm text-muted">Loading insights…</p>
      ) : (
        <>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <Tile label="Units sold" value={d.units_sold} />
            <Tile label="Revenue" value={peso(d.revenue)} />
            <Tile label="Orders" value={d.orders_count} />
            <Tile label="Avg rating" value={d.rating_count ? `${d.rating_avg ?? '—'}★ · ${d.rating_count}` : '—'} />
          </div>

          <div className="grid grid-cols-3 gap-3">
            <Meter label="On-time delivery" value={d.on_time_rate} tone="var(--status-success, #3f7d4a)" />
            <Meter label="Defect / rework" value={d.defect_rate} tone="var(--status-danger, #b0402f)" />
            <Meter label="Delayed orders" value={d.delayed_rate} tone="var(--status-alert, #b5731a)" />
          </div>

          <div>
            <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted">Orders by stage</p>
            <DistributionBars items={distribution} />
          </div>
        </>
      )}
    </Card>
  )
}
