import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { StatusPill } from '@/components/ui/status-pill'
import { FsmStepper } from '@/components/orders/fsm-stepper'
import { useOrder, useRecordPayment, useTransition } from '@/hooks/use-orders'
import { useProduction } from '@/hooks/use-manufacturing'
import { ProductionItem } from '@/components/manufacturing/production-item'
import { useAuth } from '@/hooks/use-auth'
import { STATUS_META, peso, type OrderState } from '@/lib/status'
import { AxiosError } from 'axios'

const PRODUCTION_STATES: OrderState[] = ['IN_PRODUCTION', 'QUALITY_CHECK', 'REWORK']
// Delivery-stage moves are driven by the Deliveries workflow (assign a driver → mark
// picked up → capture proof). Firing them as generic FSM buttons from the order page
// would either desync the delivery record or fail the proof guard, so they're not
// offered here — the order page points to Deliveries instead.
const DELIVERY_STATES: OrderState[] = ['OUT_FOR_DELIVERY', 'DELIVERED']
const DELIVERY_HANDOFF_STATES: OrderState[] = ['READY_FOR_DELIVERY', 'OUT_FOR_DELIVERY']

function ProductionSection({ orderId }: { orderId: number }) {
  const { data } = useProduction(orderId)
  if (!data?.items.length) return null
  return (
    <Card className="space-y-4">
      <p className="text-xs font-medium uppercase tracking-wide text-muted">Production</p>
      {data.items.map((item) => (
        <ProductionItem key={item.id} item={item} orderId={orderId} />
      ))}
    </Card>
  )
}

function TransitionActions({ orderId, allowed }: { orderId: number; allowed: OrderState[] }) {
  const transition = useTransition(orderId)
  const { has } = useAuth()
  // Staff see every allowed transition their role owns; customers only Cancel.
  // Delivery-stage moves are excluded here — they belong to the Deliveries workflow.
  const base = has('orders.viewAny') ? allowed : allowed.filter((s) => s === 'CANCELLED')
  const visible = base.filter((s) => !DELIVERY_STATES.includes(s))
  if (!visible.length) return null

  // Surface the server's actual reason (guard message / 403) rather than assuming it's
  // always a role problem — admins can perform every transition, so the old blanket
  // "your role can't perform that transition" message was misleading.
  const error = transition.isError
    ? ((transition.error as AxiosError<{ message?: string }>).response?.data?.message ??
      "That transition isn't allowed right now.")
    : null

  return (
    <Card className="space-y-2">
      <p className="text-xs font-medium uppercase tracking-wide text-muted">Move order forward</p>
      <div className="flex flex-wrap gap-2">
        {visible.map((to) => (
          <Button
            key={to}
            size="sm"
            variant={to === 'CANCELLED' ? 'danger' : 'secondary'}
            disabled={transition.isPending}
            onClick={() => transition.mutate({ to })}
          >
            {STATUS_META[to].label}
          </Button>
        ))}
      </div>
      {error && <p className="text-sm text-[var(--status-danger)]">{error}</p>}
    </Card>
  )
}

/** Delivery-stage orders are advanced from the Deliveries page (driver assignment + proof). */
function DeliveryHandoff({ status }: { status: OrderState }) {
  const { has } = useAuth()
  if (!has('delivery.view') || !DELIVERY_HANDOFF_STATES.includes(status)) return null

  return (
    <Card className="space-y-2">
      <p className="text-xs font-medium uppercase tracking-wide text-muted">Delivery</p>
      <p className="text-sm text-muted">
        {status === 'READY_FOR_DELIVERY'
          ? 'Assign a driver and mark this order picked up from the Deliveries page.'
          : 'Log location and capture proof of delivery from the Deliveries page.'}
      </p>
      <Link to="/deliveries" className="inline-flex w-fit items-center gap-1.5 text-sm text-walnut hover:underline">
        Go to Deliveries →
      </Link>
    </Card>
  )
}

function PaymentPanel({ orderId, total, paid }: { orderId: number; total: string; paid: string }) {
  const record = useRecordPayment(orderId)
  const [amount, setAmount] = useState('')
  const balance = parseFloat(total) - parseFloat(paid)

  return (
    <Card className="space-y-3">
      <p className="text-xs font-medium uppercase tracking-wide text-muted">Record payment</p>
      <p className="text-sm text-muted">Balance: <span className="text-fg">{peso(balance)}</span></p>
      <div className="flex gap-2">
        <input
          type="number"
          min="0.01"
          step="0.01"
          value={amount}
          onChange={(e) => setAmount(e.target.value)}
          placeholder="Amount"
          className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
        />
        <Button
          size="sm"
          disabled={record.isPending || !amount}
          onClick={() => record.mutate({ amount: Number(amount) }, { onSuccess: () => setAmount('') })}
        >
          Record
        </Button>
      </div>
    </Card>
  )
}

export function OrderDetailPage() {
  const { id } = useParams()
  const orderId = Number(id)
  const { data: order, isLoading } = useOrder(orderId)
  const { user, has } = useAuth()

  if (isLoading) return <div className="p-8 text-center text-muted">Loading…</div>
  if (!order) return <div className="p-8 text-center text-muted">Order not found.</div>

  const isAdmin = user?.roles.includes('admin') ?? false
  const showProduction = has('manufacturing.view') && PRODUCTION_STATES.includes(order.status)

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <Link to="/orders" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
        <ArrowLeft size={15} /> Back to orders
      </Link>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="font-mono text-xs text-muted">{order.order_number}</p>
          <h1 className="font-display text-3xl text-fg">{peso(order.total)}</h1>
          <p className="text-sm text-muted">{order.customer?.name}</p>
        </div>
        <StatusPill state={order.status} />
      </div>

      <Card><FsmStepper state={order.status} /></Card>

      <div className="grid gap-6 lg:grid-cols-5">
        <div className="space-y-6 lg:col-span-3">
          {showProduction && <ProductionSection orderId={order.id} />}

          <Card className="p-0">
            <div className="border-b border-border px-6 py-4"><h2 className="font-display text-lg text-fg">Items</h2></div>
            <table className="w-full text-sm">
              <tbody>
                {order.items?.map((it) => (
                  <tr key={it.id} className="border-b border-border last:border-0">
                    <td className="px-6 py-3 text-fg">{it.product_name}</td>
                    <td className="px-6 py-3 text-muted">×{it.quantity}</td>
                    <td className="px-6 py-3 text-right text-muted">{peso(it.unit_price)}</td>
                    <td className="px-6 py-3 text-right font-mono text-fg">{peso(it.line_total)}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="border-t border-border">
                  <td colSpan={3} className="px-6 py-3 text-right text-muted">Total</td>
                  <td className="px-6 py-3 text-right font-display text-lg text-fg">{peso(order.total)}</td>
                </tr>
              </tfoot>
            </table>
          </Card>

          <Card>
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-muted">History</p>
            <ol className="space-y-3">
              {order.transitions?.map((t) => (
                <li key={t.id} className="flex gap-3 text-sm">
                  <span className="mt-1 h-2 w-2 shrink-0 rounded-full" style={{ backgroundColor: STATUS_META[t.to_state].color }} />
                  <div>
                    <p className="text-fg">{t.from_state ? `${STATUS_META[t.from_state].label} → ` : ''}{t.to_label}</p>
                    <p className="text-xs text-muted">{t.actor ?? 'System'} · {new Date(t.created_at).toLocaleString()}</p>
                  </div>
                </li>
              ))}
            </ol>
          </Card>
        </div>

        <div className="space-y-6 lg:col-span-2">
          <TransitionActions orderId={order.id} allowed={order.allowed_transitions} />
          <DeliveryHandoff status={order.status} />

          <Card className="space-y-2 text-sm">
            <p className="text-xs font-medium uppercase tracking-wide text-muted">Payment</p>
            <div className="flex justify-between"><span className="text-muted">Status</span><span className="text-fg">{order.payment_status}</span></div>
            <div className="flex justify-between"><span className="text-muted">Paid</span><span className="text-fg">{peso(order.amount_paid)}</span></div>
            <div className="flex justify-between"><span className="text-muted">Total</span><span className="text-fg">{peso(order.total)}</span></div>
          </Card>

          {isAdmin && <PaymentPanel orderId={order.id} total={order.total} paid={order.amount_paid} />}

          {order.delivery_address && (
            <Card>
              <p className="mb-1 text-xs font-medium uppercase tracking-wide text-muted">Delivery address</p>
              <p className="text-sm text-fg">{order.delivery_address}</p>
            </Card>
          )}
        </div>
      </div>
    </div>
  )
}
