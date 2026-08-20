import { Check, AlertTriangle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/use-auth'
import { useStageActions } from '@/hooks/use-manufacturing'
import type { ProductionItem as Item, Stage, StageStatus } from '@/lib/manufacturing-api'

const STAGE_COLOR: Record<StageStatus, string> = {
  pending: 'var(--status-neutral)',
  in_progress: 'var(--status-active)',
  done: 'var(--status-success)',
  blocked: 'var(--status-danger)',
}

function StageBlock({ stage, itemId, orderId }: { stage: Stage; itemId: number; orderId?: number }) {
  const { has } = useAuth()
  const { start, complete } = useStageActions(orderId)
  const isQc = stage.stage === 'qc'
  const canAct = isQc ? has('manufacturing.verify') : has('manufacturing.stage.update')
  const busy = start.isPending || complete.isPending
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
              <Button size="sm" className="h-6 px-2 text-[11px]" disabled={busy} onClick={() => complete.mutate({ itemId, stage: stage.stage, qcPassed: true })}>Pass</Button>
              <Button size="sm" variant="danger" className="h-6 px-2 text-[11px]" disabled={busy} onClick={() => complete.mutate({ itemId, stage: stage.stage, qcPassed: false })}>Fail</Button>
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

export function ProductionItem({ item, orderId }: { item: Item; orderId?: number }) {
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
      <div className="flex flex-wrap gap-2">
        {item.stages.map((s) => (
          <StageBlock key={s.id} stage={s} itemId={item.id} orderId={orderId} />
        ))}
      </div>
    </div>
  )
}
