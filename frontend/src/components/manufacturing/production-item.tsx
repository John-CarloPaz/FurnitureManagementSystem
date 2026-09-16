import { useState } from 'react'
import { Check, AlertTriangle, Camera, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/use-auth'
import { useStageActions } from '@/hooks/use-manufacturing'
import { qcPhotoUrl, type ProductionItem as Item, type Stage, type StageStatus } from '@/lib/manufacturing-api'

const STAGE_COLOR: Record<StageStatus, string> = {
  pending: 'var(--status-neutral)',
  in_progress: 'var(--status-active)',
  done: 'var(--status-success)',
  blocked: 'var(--status-danger)',
}

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

function StageBlock({
  stage,
  itemId,
  orderId,
  onQcFail,
}: {
  stage: Stage
  itemId: number
  orderId?: number
  onQcFail: () => void
}) {
  const { has } = useAuth()
  const { start, complete, qc } = useStageActions(orderId)
  const isQc = stage.stage === 'qc'
  const canAct = isQc ? has('manufacturing.verify') : has('manufacturing.stage.update')
  const busy = start.isPending || complete.isPending || qc.isPending
  const color = stage.is_delayed && stage.status !== 'done' ? 'var(--status-alert)' : STAGE_COLOR[stage.status]

  return (
    <div className="flex min-w-[130px] flex-col gap-1.5 rounded-[var(--radius-sm)] border border-border bg-surface-2 p-2.5">
      <div className="flex items-center gap-1.5">
        <span className="h-2 w-2 rounded-full" style={{ backgroundColor: color }} />
        <span className="text-xs font-medium text-fg">{stage.stage_label}</span>
        {stage.is_delayed && stage.status === 'done' && <AlertTriangle size={12} style={{ color: 'var(--status-alert)' }} />}
      </div>

      {stage.status === 'done' ? (
        <span className="flex items-center gap-1 text-xs" style={{ color: isQc && stage.qc_passed === false ? 'var(--status-danger)' : 'var(--status-success)' }}>
          <Check size={12} /> {isQc ? (stage.qc_passed === false ? 'Failed' : 'Passed') : 'Done'}
        </span>
      ) : canAct ? (
        <div className="flex flex-wrap gap-1">
          {isQc ? (
            <>
              <Button size="sm" className="h-6 px-2 text-[11px]" disabled={busy} onClick={() => qc.mutate({ itemId, passed: true })}>Pass</Button>
              <Button size="sm" variant="danger" className="h-6 px-2 text-[11px]" disabled={busy} onClick={onQcFail}>Fail</Button>
            </>
          ) : (
            <>
              {stage.status === 'pending' && (
                <Button size="sm" variant="secondary" className="h-6 px-2 text-[11px]" disabled={busy} onClick={() => start.mutate({ itemId, stage: stage.stage })}>Start</Button>
              )}
              <Button size="sm" className="h-6 px-2 text-[11px]" disabled={busy} onClick={() => complete.mutate({ itemId, stage: stage.stage })}>Done</Button>
            </>
          )}
        </div>
      ) : (
        <span className="text-xs text-muted">{stage.status_label}</span>
      )}
    </div>
  )
}

function QcFailForm({ itemId, orderId, onClose }: { itemId: number; orderId?: number; onClose: () => void }) {
  const { qc } = useStageActions(orderId)
  const [reason, setReason] = useState('')
  const [photos, setPhotos] = useState<File[]>([])

  const submit = () =>
    qc.mutate({ itemId, passed: false, reason, photos }, { onSuccess: onClose })

  return (
    <div className="space-y-2 rounded-[var(--radius-sm)] border border-[var(--status-danger)] bg-surface-2 p-3">
      <div className="flex items-center justify-between">
        <p className="text-xs font-medium uppercase tracking-wide text-[var(--status-danger)]">Report QC failure</p>
        <button onClick={onClose} className="text-muted hover:text-fg"><X size={14} /></button>
      </div>
      <textarea
        rows={2}
        value={reason}
        onChange={(e) => setReason(e.target.value)}
        placeholder="What's wrong with the item? (required)"
        className={inputCls}
      />
      <label className="flex cursor-pointer items-center gap-2 text-xs text-walnut">
        <Camera size={14} /> {photos.length ? `${photos.length} photo${photos.length > 1 ? 's' : ''} attached` : 'Attach defect photos'}
        <input type="file" accept="image/*" multiple onChange={(e) => setPhotos(Array.from(e.target.files ?? []))} className="hidden" />
      </label>
      <div className="flex gap-2">
        <Button size="sm" variant="danger" disabled={qc.isPending || !reason.trim()} onClick={submit}>
          {qc.isPending ? 'Sending…' : 'Fail & send back to production'}
        </Button>
        <Button size="sm" variant="ghost" onClick={onClose} disabled={qc.isPending}>Cancel</Button>
      </div>
    </div>
  )
}

export function ProductionItem({ item, orderId }: { item: Item; orderId?: number }) {
  const [failing, setFailing] = useState(false)
  // Inspections come newest-first; surface the most recent failure as rework feedback.
  const latest = item.qc_inspections?.[0]
  const lastFailure = latest && !latest.passed ? latest : undefined

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-fg">
          {item.product_name} <span className="text-muted">×{item.quantity}</span>
        </p>
        <span className="text-xs text-muted">{item.percent}%</span>
      </div>
      <div className="h-1.5 w-full overflow-hidden rounded-full bg-surface-2">
        <div className="h-full rounded-full bg-walnut transition-all" style={{ width: `${item.percent}%` }} />
      </div>

      {lastFailure && (
        <div className="space-y-1.5 rounded-[var(--radius-sm)] border border-border bg-surface-2 p-2.5">
          <p className="flex items-center gap-1.5 text-xs font-medium text-[var(--status-danger)]">
            <AlertTriangle size={13} /> QC attempt {lastFailure.attempt} failed{lastFailure.inspector ? ` · ${lastFailure.inspector}` : ''}
          </p>
          {lastFailure.reason && <p className="text-xs text-fg">{lastFailure.reason}</p>}
          {lastFailure.photos.length > 0 && (
            <div className="flex flex-wrap gap-1.5">
              {lastFailure.photos.map((photo) => (
                <a key={photo.id} href={qcPhotoUrl(photo.url)} target="_blank" rel="noreferrer">
                  <img src={qcPhotoUrl(photo.url)} alt="QC defect" className="h-12 w-12 rounded object-cover ring-1 ring-border" />
                </a>
              ))}
            </div>
          )}
        </div>
      )}

      <div className="flex flex-wrap gap-2">
        {item.stages.map((s) => (
          <StageBlock key={s.id} stage={s} itemId={item.id} orderId={orderId} onQcFail={() => setFailing(true)} />
        ))}
      </div>

      {failing && <QcFailForm itemId={item.id} orderId={orderId} onClose={() => setFailing(false)} />}
    </div>
  )
}
