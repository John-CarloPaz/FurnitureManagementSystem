import { useParams, Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { StatusPill } from '@/components/ui/status-pill'
import { FsmStepper } from '@/components/orders/fsm-stepper'
import { DeliveryTracking } from '@/components/delivery/delivery-tracking'
import { useOrder } from '@/hooks/use-orders'
import { peso } from '@/lib/status'

export function StorefrontOrderDetail() {
  const { id } = useParams()
  const { data: order, isLoading } = useOrder(Number(id))

  if (isLoading) return <p className="py-16 text-center text-muted">Loading…</p>
  if (!order) return <p className="py-16 text-center text-muted">Order not found. <Link to="/shop/orders" className="text-walnut">My orders</Link></p>

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <Link to="/shop/orders" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
        <ArrowLeft size={15} /> My orders
      </Link>

      <div className="flex flex-wrap items-center justify-between gap-2">
        <div>
          <p className="font-mono text-xs text-muted">{order.order_number}</p>
          <h1 className="font-display text-3xl text-fg">{peso(order.total)}</h1>
        </div>
        <StatusPill state={order.status} />
      </div>

      <Card><FsmStepper state={order.status} /></Card>

      <Card className="p-0">
        <div className="border-b border-border px-6 py-4"><h2 className="font-display text-lg text-fg">Items</h2></div>
        <table className="w-full text-sm">
          <tbody>
            {order.items?.map((it) => (
              <tr key={it.id} className="border-b border-border last:border-0">
                <td className="px-6 py-3 text-fg">{it.product_name}</td>
                <td className="px-6 py-3 text-muted">×{it.quantity}</td>
                <td className="px-6 py-3 text-right font-mono text-fg">{peso(it.line_total)}</td>
              </tr>
            ))}
          </tbody>
          <tfoot>
            <tr className="border-t border-border">
              <td colSpan={2} className="px-6 py-3 text-right text-muted">Total</td>
              <td className="px-6 py-3 text-right font-display text-lg text-fg">{peso(order.total)}</td>
            </tr>
          </tfoot>
        </table>
      </Card>

      {order.delivery && (
        <Card className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-lg text-fg">Delivery tracking</h2>
            <span className="text-sm text-muted">{order.delivery.status_label}</span>
          </div>
          {order.delivery.driver && <p className="text-sm text-muted">Your rider: <span className="text-fg">{order.delivery.driver}</span></p>}
          <DeliveryTracking delivery={order.delivery} />
        </Card>
      )}

      {order.delivery_address && (
        <Card>
          <p className="mb-1 text-xs font-medium uppercase tracking-wide text-muted">Delivering to</p>
          <p className="text-sm text-fg">{order.delivery_address}</p>
        </Card>
      )}
    </div>
  )
}
