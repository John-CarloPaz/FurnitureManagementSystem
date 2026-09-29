import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ArrowLeft, Wallet, RotateCcw, LifeBuoy } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { StatusPill } from '@/components/ui/status-pill'
import { FsmStepper } from '@/components/orders/fsm-stepper'
import { DeliveryTracking } from '@/components/delivery/delivery-tracking'
import { useOrder, usePayOrder } from '@/hooks/use-orders'
import { useCreateReturn } from '@/hooks/use-returns'
import { useCreateIssue } from '@/hooks/use-issues'
import { RETURN_REASONS, labelize } from '@/lib/returns-api'
import { ISSUE_CATEGORIES } from '@/lib/issues-api'
import { peso } from '@/lib/status'

export function StorefrontOrderDetail() {
  const { id } = useParams()
  const orderId = Number(id)
  const { data: order, isLoading } = useOrder(orderId)
  const pay = usePayOrder(orderId)
  const createReturn = useCreateReturn(orderId)
  const createIssue = useCreateIssue(orderId)
  const [reference, setReference] = useState('')
  const [returnReason, setReturnReason] = useState(RETURN_REASONS[0] as string)
  const [returnDesc, setReturnDesc] = useState('')
  const [issueCat, setIssueCat] = useState(ISSUE_CATEGORIES[0] as string)
  const [issueDesc, setIssueDesc] = useState('')

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

      {order.payment_status !== 'PAID' && (order.payment_method === 'GCASH' || order.payment_method === 'BANK') && (
        <Card className="space-y-3 border-walnut" >
          <h2 className="flex items-center gap-2 font-display text-lg text-fg"><Wallet size={18} /> Complete your payment</h2>
          <p className="text-sm text-muted">
            {order.payment_method === 'GCASH'
              ? <>Send <b className="text-fg">{peso(order.total)}</b> to GCash <b className="text-fg">0917-000-0000</b> (Cedarside Holding Corp.), then enter your reference number below.</>
              : <>Transfer <b className="text-fg">{peso(order.total)}</b> to <b className="text-fg">BPI 1234-5678-90</b> (Cedarside Holding Corp.), then enter your reference number below.</>}
          </p>
          <div className="flex flex-wrap gap-2">
            <input value={reference} onChange={(e) => setReference(e.target.value)} placeholder="Reference number (optional)" className="flex-1 rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]" />
            <Button size="pill" onClick={() => pay.mutate({ reference: reference.trim() || undefined })} disabled={pay.isPending}>
              {pay.isPending ? 'Confirming…' : "I've paid"}
            </Button>
          </div>
          {pay.isError && <p className="text-sm text-[var(--status-danger)]">Couldn't confirm payment. Please try again.</p>}
        </Card>
      )}

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
        </table>
        <div className="space-y-1.5 border-t border-border px-6 py-4 text-sm">
          <div className="flex justify-between text-muted"><span>Subtotal</span><span className="font-mono text-fg">{peso(order.subtotal)}</span></div>
          {Number(order.discount_amount) > 0 && (
            <div className="flex justify-between text-muted"><span>Discount{order.voucher_code ? ` (${order.voucher_code})` : ''}</span><span className="font-mono">−{peso(order.discount_amount)}</span></div>
          )}
          <div className="flex justify-between text-muted"><span>Shipping fee</span><span className="font-mono text-fg">{peso(order.delivery_fee)}</span></div>
          <div className="flex justify-between text-muted"><span>VAT</span><span className="font-mono text-fg">{peso(order.tax_amount)}</span></div>
          <div className="flex justify-between border-t border-border pt-2 text-base">
            <span className="font-medium text-fg">Total</span>
            <span className="font-display text-xl text-fg">{peso(order.total)}</span>
          </div>
          {order.payment_method && (
            <p className="pt-1 text-xs text-muted">Payment: {order.payment_method} · {order.payment_status}</p>
          )}
        </div>
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

      {(order.status === 'DELIVERED' || order.status === 'COMPLETED') && (
        <>
          {/* Return / refund */}
          <Card className="space-y-3">
            <h2 className="flex items-center gap-2 font-display text-lg text-fg"><RotateCcw size={17} /> Return or refund</h2>
            {order.returns && order.returns.length > 0 ? (
              <div className="rounded-[var(--radius-sm)] border border-border bg-surface-2 p-3 text-sm">
                <p className="text-fg"><b>{labelize(order.returns[0].reason)}</b> — {labelize(order.returns[0].status)}</p>
                {order.returns[0].resolution_note && <p className="text-muted">{order.returns[0].resolution_note}</p>}
                {order.returns[0].refund_amount && <p className="text-muted">Refund: {peso(order.returns[0].refund_amount)}</p>}
              </div>
            ) : (
              <div className="space-y-2">
                <div className="flex flex-wrap gap-2">
                  <select value={returnReason} onChange={(e) => setReturnReason(e.target.value)} className="rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg">
                    {RETURN_REASONS.map((r) => <option key={r} value={r}>{labelize(r)}</option>)}
                  </select>
                </div>
                <textarea rows={2} value={returnDesc} onChange={(e) => setReturnDesc(e.target.value)} placeholder="Tell us what's wrong (optional)" className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]" />
                <Button size="pill" onClick={() => createReturn.mutate({ reason: returnReason, description: returnDesc.trim() || undefined })} disabled={createReturn.isPending}>
                  {createReturn.isPending ? 'Submitting…' : 'Request return/refund'}
                </Button>
                {createReturn.isError && <p className="text-sm text-[var(--status-danger)]">Couldn't submit the request.</p>}
              </div>
            )}
          </Card>

          {/* Report an issue */}
          <Card className="space-y-3">
            <h2 className="flex items-center gap-2 font-display text-lg text-fg"><LifeBuoy size={17} /> Report an issue</h2>
            {order.issues && order.issues.length > 0 && (
              <div className="space-y-2">
                {order.issues.map((i) => (
                  <div key={i.id} className="rounded-[var(--radius-sm)] border border-border bg-surface-2 p-3 text-sm">
                    <p className="text-fg"><b>{labelize(i.category)}</b> — {labelize(i.status)}</p>
                    <p className="text-muted">{i.description}</p>
                    {i.resolution_note && <p className="text-muted">Resolution: {i.resolution_note}</p>}
                  </div>
                ))}
              </div>
            )}
            <div className="space-y-2">
              <select value={issueCat} onChange={(e) => setIssueCat(e.target.value)} className="rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg">
                {ISSUE_CATEGORIES.map((c) => <option key={c} value={c}>{labelize(c)}</option>)}
              </select>
              <textarea rows={2} value={issueDesc} onChange={(e) => setIssueDesc(e.target.value)} placeholder="Describe the problem" className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]" />
              <Button size="pill" onClick={() => { if (issueDesc.trim()) createIssue.mutate({ category: issueCat, description: issueDesc.trim() }, { onSuccess: () => setIssueDesc('') }) }} disabled={createIssue.isPending || !issueDesc.trim()}>
                {createIssue.isPending ? 'Submitting…' : 'Report issue'}
              </Button>
            </div>
          </Card>
        </>
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
