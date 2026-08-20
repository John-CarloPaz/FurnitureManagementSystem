import { Link } from 'react-router-dom'
import { AlertTriangle, Radio } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { StatusPill } from '@/components/ui/status-pill'
import { ProductionItem } from '@/components/manufacturing/production-item'
import { useShopFloor } from '@/hooks/use-manufacturing'
import type { OrderState } from '@/lib/status'

export function ShopFloorPage() {
  const { data, isLoading } = useShopFloor()
  const delayed = data?.filter((o) => o.has_delay).length ?? 0

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div className="flex items-center justify-between">
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

      {isLoading ? (
        <p className="p-8 text-center text-muted">Loading shop floor…</p>
      ) : !data?.length ? (
        <Card className="text-center text-muted">Nothing in production right now.</Card>
      ) : (
        <div className="space-y-4">
          {data.map((order) => (
            <Card key={order.id} className="space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-3">
                  <Link to={`/orders/${order.id}`} className="font-mono text-xs text-muted hover:text-walnut">
                    {order.order_number}
                  </Link>
                  <span className="text-sm text-fg">{order.customer}</span>
                </div>
                <div className="flex items-center gap-2">
                  {order.has_delay && <AlertTriangle size={16} style={{ color: 'var(--status-alert)' }} />}
                  <StatusPill state={order.status as OrderState} />
                </div>
              </div>
              <div className="space-y-4">
                {order.items.map((item) => (
                  <ProductionItem key={item.id} item={item} orderId={order.id} />
                ))}
              </div>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
