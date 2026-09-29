import { useState } from 'react'
import { RotateCcw } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/use-auth'
import { useReturns, useUpdateReturn } from '@/hooks/use-returns'
import { labelize, RETURN_STATUSES, type ReturnRequest } from '@/lib/returns-api'
import { peso } from '@/lib/status'

const STATUS_COLOR: Record<string, string> = {
  REQUESTED: 'var(--status-active)',
  APPROVED: 'var(--status-success)',
  REJECTED: 'var(--status-danger)',
  REFUNDED: 'var(--walnut)',
}

function ReturnRow({ r, canManage }: { r: ReturnRequest; canManage: boolean }) {
  const update = useUpdateReturn()
  const [status, setStatus] = useState(r.status)
  const [note, setNote] = useState(r.resolution_note ?? '')
  const [refund, setRefund] = useState(r.refund_amount ?? '')

  const save = () =>
    update.mutate({ id: r.id, status, resolution_note: note.trim() || undefined, refund_amount: refund ? Number(refund) : undefined })

  return (
    <Card className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div>
          <p className="font-medium text-fg">{r.order_number ?? `Order #${r.order_id}`} · <span className="text-muted">{labelize(r.reason)}</span></p>
          <p className="text-xs text-muted">By {r.requester ?? 'customer'} · {new Date(r.created_at).toLocaleDateString()}</p>
        </div>
        <span className="rounded-full px-2.5 py-0.5 text-xs font-medium text-white" style={{ backgroundColor: STATUS_COLOR[r.status] ?? 'var(--status-neutral)' }}>{labelize(r.status)}</span>
      </div>
      {r.description && <p className="text-sm text-muted">{r.description}</p>}
      {r.refund_amount && <p className="text-sm text-fg">Refund: {peso(r.refund_amount)}</p>}
      {r.resolution_note && <p className="text-sm text-muted">Note: {r.resolution_note}</p>}

      {canManage && (
        <div className="flex flex-wrap items-end gap-2 border-t border-border pt-3">
          <label className="text-sm">
            <span className="mb-1 block text-xs text-muted">Status</span>
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-[var(--radius-sm)] border border-border bg-bg px-2 py-1.5 text-sm text-fg">
              {RETURN_STATUSES.map((s) => <option key={s} value={s}>{labelize(s)}</option>)}
            </select>
          </label>
          {status === 'REFUNDED' && (
            <label className="text-sm">
              <span className="mb-1 block text-xs text-muted">Refund ₱</span>
              <input type="number" min="0" value={refund} onChange={(e) => setRefund(e.target.value)} placeholder="full total" className="w-28 rounded-[var(--radius-sm)] border border-border bg-bg px-2 py-1.5 text-sm text-fg" />
            </label>
          )}
          <label className="flex-1 text-sm">
            <span className="mb-1 block text-xs text-muted">Resolution note</span>
            <input value={note} onChange={(e) => setNote(e.target.value)} className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-2 py-1.5 text-sm text-fg" />
          </label>
          <Button size="pill" onClick={save} disabled={update.isPending}>Save</Button>
        </div>
      )}
    </Card>
  )
}

export function ReturnsPage() {
  const { has } = useAuth()
  const canManage = has('returns.manage')
  const { data: returns = [], isLoading } = useReturns()

  return (
    <div className="space-y-6">
      <div>
        <h1 className="flex items-center gap-2 font-display text-2xl text-fg"><RotateCcw size={22} /> Returns &amp; Refunds</h1>
        <p className="text-sm text-muted">Customer return requests on delivered orders.</p>
      </div>
      {isLoading ? (
        <p className="py-10 text-center text-muted">Loading…</p>
      ) : !returns.length ? (
        <Card className="text-center text-muted">No return requests yet.</Card>
      ) : (
        <div className="space-y-3">{returns.map((r) => <ReturnRow key={r.id} r={r} canManage={canManage} />)}</div>
      )}
    </div>
  )
}
