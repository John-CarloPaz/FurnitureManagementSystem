import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { AlertTriangle, Radio, Search } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { useShopFloor } from '@/hooks/use-manufacturing'
import { STATUS_META, type OrderState } from '@/lib/status'
import type { ShopFloorOrder } from '@/lib/manufacturing-api'

// The shop-floor pipeline stages, left to right.
const COLUMNS: OrderState[] = ['IN_PRODUCTION', 'QUALITY_CHECK', 'REWORK']

function orderProgress(o: ShopFloorOrder): number {
  if (!o.items.length) return 0
  return Math.round(o.items.reduce((s, it) => s + it.percent, 0) / o.items.length)
}

function OrderCard({ order, onClick }: { order: ShopFloorOrder; onClick: () => void }) {
  const progress = orderProgress(order)
  return (
    <button
      onClick={onClick}
      className="w-full space-y-2 rounded-[var(--radius-md)] border border-border bg-surface p-3 text-left shadow-[var(--shadow-sm)] transition-colors hover:border-walnut"
    >
      <div className="flex items-center justify-between gap-2">
        <span className="font-mono text-xs text-muted">{order.order_number}</span>
        {order.has_delay && <AlertTriangle size={14} style={{ color: 'var(--status-alert)' }} />}
      </div>
      <p className="text-sm font-medium text-fg">{order.customer ?? '—'}</p>
      <p className="text-xs text-muted">{order.items.length} item{order.items.length !== 1 ? 's' : ''}</p>
      <div className="h-1.5 w-full overflow-hidden rounded-full bg-surface-2">
        <div className="h-full rounded-full bg-walnut transition-all" style={{ width: `${progress}%` }} />
      </div>
      <p className="text-right text-[11px] text-muted">{progress}%</p>
    </button>
  )
}

export function ShopFloorPage() {
  const { data, isLoading } = useShopFloor()
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const [delayedOnly, setDelayedOnly] = useState(false)

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    return (data ?? []).filter(
      (o) =>
        (!delayedOnly || o.has_delay) &&
        (!q || o.order_number.toLowerCase().includes(q) || (o.customer ?? '').toLowerCase().includes(q)),
    )
  }, [data, search, delayedOnly])

  const delayed = data?.filter((o) => o.has_delay).length ?? 0

  return (
    <div className="mx-auto max-w-7xl space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-3xl text-fg">Shop Floor</h1>
          <p className="mt-1 flex items-center gap-1.5 text-muted">
            <Radio size={14} style={{ color: 'var(--status-success)' }} /> Live · updates every 10s
          </p>
        </div>
        {delayed > 0 && (
          <span
            className="flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium"
            style={{ backgroundColor: 'color-mix(in srgb, var(--status-alert) 14%, transparent)', color: 'var(--status-alert)' }}
          >
            <AlertTriangle size={15} /> {delayed} delayed
          </span>
        )}
      </div>

      <div className="flex flex-wrap items-center gap-3">
        <div className="relative flex-1 sm:max-w-xs">
          <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search order or customer…"
            className="w-full rounded-[var(--radius-sm)] border border-border bg-bg py-2 pl-9 pr-3 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
          />
        </div>
        <button
          onClick={() => setDelayedOnly((d) => !d)}
          className="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
          style={
            delayedOnly
              ? { borderColor: 'var(--status-alert)', color: 'var(--status-alert)', backgroundColor: 'color-mix(in srgb, var(--status-alert) 12%, transparent)' }
              : { borderColor: 'var(--border)', color: 'var(--muted)' }
          }
        >
          Delayed only
        </button>
      </div>

      {isLoading ? (
        <p className="p-8 text-center text-muted">Loading shop floor…</p>
      ) : !data?.length ? (
        <Card className="text-center text-muted">Nothing in production right now.</Card>
      ) : (
        <div className="flex gap-4 overflow-x-auto pb-2">
          {COLUMNS.map((status) => {
            const orders = filtered.filter((o) => o.status === status)
            const meta = STATUS_META[status]
            return (
              <div key={status} className="flex w-72 shrink-0 flex-col gap-3">
                <div className="flex items-center gap-2 px-1">
                  <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: meta.color }} />
                  <span className="text-sm font-medium text-fg">{meta.label}</span>
                  <span className="ml-auto rounded-full bg-surface-2 px-2 py-0.5 text-xs font-medium text-muted">{orders.length}</span>
                </div>
                <div className="flex flex-col gap-2 rounded-[var(--radius-md)] bg-surface-2/40 p-2">
                  {orders.length === 0 ? (
                    <p className="py-6 text-center text-xs text-muted">Empty</p>
                  ) : (
                    orders.map((o) => <OrderCard key={o.id} order={o} onClick={() => navigate(`/orders/${o.id}`)} />)
                  )}
                </div>
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}
