import { useState } from 'react'
import { ChevronLeft, ChevronRight, Download } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useAuditLogs } from '@/hooks/use-audit'
import { exportAuditLogs, type AuditEvent, type AuditLog } from '@/lib/audit-api'

const METHOD_COLOR: Record<string, string> = {
  POST: 'var(--status-success)',
  PATCH: 'var(--status-warning)',
  PUT: 'var(--status-warning)',
  DELETE: 'var(--status-danger)',
}

const EVENT_COLOR: Record<AuditEvent, string> = {
  created: 'var(--status-success)',
  updated: 'var(--status-warning)',
  deleted: 'var(--status-danger)',
}

const EVENTS: (AuditEvent | 'all')[] = ['all', 'created', 'updated', 'deleted']

function renderValue(v: unknown): string {
  if (v === null || v === undefined) return '∅'
  if (typeof v === 'boolean') return v ? 'true' : 'false'
  const s = typeof v === 'object' ? JSON.stringify(v) : String(v)
  return s.length > 48 ? `${s.slice(0, 48)}…` : s
}

/** A change entry is either { old, new } (update) or a bare value (create). */
function isPair(v: unknown): v is { old: unknown; new: unknown } {
  return typeof v === 'object' && v !== null && 'old' in v && 'new' in v
}

function Changes({ log }: { log: AuditLog }) {
  if (log.event === 'deleted' || !log.changes || Object.keys(log.changes).length === 0) {
    return <span className="text-xs text-muted">—</span>
  }
  return (
    <div className="flex flex-col gap-0.5">
      {Object.entries(log.changes).map(([field, val]) => (
        <div key={field} className="text-xs">
          <span className="font-medium text-fg">{field}</span>{' '}
          {isPair(val) ? (
            <span className="text-muted">
              {renderValue(val.old)} <span className="text-fg">→</span> {renderValue(val.new)}
            </span>
          ) : (
            <span className="text-muted">{renderValue(val)}</span>
          )}
        </div>
      ))}
    </div>
  )
}

export function AuditPage() {
  const [page, setPage] = useState(1)
  const [event, setEvent] = useState<AuditEvent | 'all'>('all')
  const [exporting, setExporting] = useState(false)
  const { data, isLoading, isFetching } = useAuditLogs(page, { event: event === 'all' ? undefined : event })

  const rows = data?.data ?? []
  const meta = data?.meta

  const onExport = async () => {
    setExporting(true)
    try {
      const blob = await exportAuditLogs({ event: event === 'all' ? undefined : event })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `audit-log-${new Date().toISOString().slice(0, 10)}.csv`
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
    } finally {
      setExporting(false)
    }
  }

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-3xl text-fg">Audit Log</h1>
          <p className="mt-1 text-muted">Who edited what — every change, with the fields that changed.</p>
        </div>
        <Button variant="secondary" size="pill" onClick={onExport} disabled={exporting || !rows.length}>
          <Download size={16} /> {exporting ? 'Exporting…' : 'Export CSV'}
        </Button>
      </div>

      <div className="flex flex-wrap gap-2">
        {EVENTS.map((e) => (
          <button
            key={e}
            onClick={() => { setEvent(e); setPage(1) }}
            className="rounded-full border px-3 py-1 text-xs font-medium capitalize transition-colors"
            style={
              event === e
                ? { borderColor: 'var(--walnut)', backgroundColor: 'var(--amber-soft)', color: 'var(--fg)' }
                : { borderColor: 'var(--border)', color: 'var(--muted)' }
            }
          >
            {e}
          </button>
        ))}
      </div>

      <Card className="p-0">
        {isLoading ? (
          <p className="p-8 text-center text-muted">Loading audit trail…</p>
        ) : !rows.length ? (
          <p className="p-8 text-center text-muted">No activity recorded yet.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-xs uppercase tracking-wide text-muted">
                  <th className="px-6 py-3 font-medium">When</th>
                  <th className="px-6 py-3 font-medium">Who</th>
                  <th className="px-6 py-3 font-medium">Method</th>
                  <th className="px-6 py-3 font-medium">Entity</th>
                  <th className="px-6 py-3 font-medium">Fields changed</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((log) => (
                  <tr key={log.id} className="border-t border-border align-top">
                    <td className="whitespace-nowrap px-6 py-3 text-xs text-muted">
                      {new Date(log.created_at).toLocaleString()}
                    </td>
                    <td className="px-6 py-3 text-fg">{log.user ?? 'System'}</td>
                    <td className="px-6 py-3">
                      <span
                        className="rounded px-1.5 py-0.5 font-mono text-[11px] font-medium"
                        style={{
                          color: METHOD_COLOR[log.method ?? ''] ?? 'var(--status-neutral)',
                          backgroundColor: `color-mix(in srgb, ${METHOD_COLOR[log.method ?? ''] ?? 'var(--status-neutral)'} 12%, transparent)`,
                        }}
                      >
                        {log.method ?? '—'}
                      </span>
                    </td>
                    <td className="whitespace-nowrap px-6 py-3">
                      <span className="text-fg">{log.entity}{log.entity_id ? ` #${log.entity_id}` : ''}</span>
                      <span className="ml-2 text-[11px] font-medium capitalize" style={{ color: EVENT_COLOR[log.event] }}>
                        {log.event}
                      </span>
                    </td>
                    <td className="px-6 py-3"><Changes log={log} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-border px-6 py-3 text-sm text-muted">
            <span>Page {meta.current_page} of {meta.last_page} · {meta.total} entries{isFetching ? ' · …' : ''}</span>
            <div className="flex gap-2">
              <button
                disabled={meta.current_page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                className="inline-flex items-center gap-1 rounded-[var(--radius-sm)] border border-border px-2 py-1 disabled:opacity-40 hover:bg-surface-2"
              >
                <ChevronLeft size={14} /> Prev
              </button>
              <button
                disabled={meta.current_page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
                className="inline-flex items-center gap-1 rounded-[var(--radius-sm)] border border-border px-2 py-1 disabled:opacity-40 hover:bg-surface-2"
              >
                Next <ChevronRight size={14} />
              </button>
            </div>
          </div>
        )}
      </Card>
    </div>
  )
}
