import { useState } from 'react'
import { LifeBuoy } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/use-auth'
import { useIssues, useUpdateIssue } from '@/hooks/use-issues'
import { ISSUE_STATUSES, type IssueReport } from '@/lib/issues-api'
import { labelize } from '@/lib/returns-api'

const STATUS_COLOR: Record<string, string> = {
  OPEN: 'var(--status-active)',
  IN_REVIEW: 'var(--status-alert)',
  RESOLVED: 'var(--status-success)',
}

function IssueRow({ i, canManage }: { i: IssueReport; canManage: boolean }) {
  const update = useUpdateIssue()
  const [status, setStatus] = useState(i.status)
  const [note, setNote] = useState(i.resolution_note ?? '')

  const save = () => update.mutate({ id: i.id, status, resolution_note: note.trim() || undefined })

  return (
    <Card className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div>
          <p className="font-medium text-fg">{i.order_number ?? `Order #${i.order_id}`} · <span className="text-muted">{labelize(i.category)}</span></p>
          <p className="text-xs text-muted">By {i.reporter ?? 'customer'} · {new Date(i.created_at).toLocaleDateString()}</p>
        </div>
        <span className="rounded-full px-2.5 py-0.5 text-xs font-medium text-white" style={{ backgroundColor: STATUS_COLOR[i.status] ?? 'var(--status-neutral)' }}>{labelize(i.status)}</span>
      </div>
      <p className="text-sm text-fg">{i.description}</p>
      {i.resolution_note && <p className="text-sm text-muted">Resolution: {i.resolution_note}</p>}

      {canManage && (
        <div className="flex flex-wrap items-end gap-2 border-t border-border pt-3">
          <label className="text-sm">
            <span className="mb-1 block text-xs text-muted">Status</span>
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-[var(--radius-sm)] border border-border bg-bg px-2 py-1.5 text-sm text-fg">
              {ISSUE_STATUSES.map((s) => <option key={s} value={s}>{labelize(s)}</option>)}
            </select>
          </label>
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

export function IssuesPage() {
  const { has } = useAuth()
  const canManage = has('issues.manage')
  const { data: issues = [], isLoading } = useIssues()

  return (
    <div className="space-y-6">
      <div>
        <h1 className="flex items-center gap-2 font-display text-2xl text-fg"><LifeBuoy size={22} /> Reported Issues</h1>
        <p className="text-sm text-muted">Problems customers reported on delivered orders.</p>
      </div>
      {isLoading ? (
        <p className="py-10 text-center text-muted">Loading…</p>
      ) : !issues.length ? (
        <Card className="text-center text-muted">No issues reported yet.</Card>
      ) : (
        <div className="space-y-3">{issues.map((i) => <IssueRow key={i.id} i={i} canManage={canManage} />)}</div>
      )}
    </div>
  )
}
