import { Check } from 'lucide-react'
import { STATUS_META, type OrderState } from '@/lib/status'
import { cn } from '@/lib/utils'

// The happy-path stages shown in the stepper (REWORK/CANCELLED are off-path).
const PATH: OrderState[] = [
  'PLACED',
  'CONFIRMED',
  'IN_PRODUCTION',
  'QUALITY_CHECK',
  'READY_FOR_DELIVERY',
  'OUT_FOR_DELIVERY',
  'DELIVERED',
  'COMPLETED',
]

export function FsmStepper({ state }: { state: OrderState }) {
  if (state === 'CANCELLED') {
    return (
      <div
        className="rounded-[var(--radius-md)] px-4 py-3 text-sm font-medium"
        style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}
      >
        This order was cancelled.
      </div>
    )
  }

  // REWORK sits at the production stage of the path.
  const effective = state === 'REWORK' ? 'IN_PRODUCTION' : state
  const currentIdx = PATH.indexOf(effective)

  return (
    <div className="flex items-center overflow-x-auto pb-2">
      {PATH.map((stage, i) => {
        const done = i < currentIdx
        const current = i === currentIdx
        const meta = STATUS_META[stage]
        return (
          <div key={stage} className="flex items-center">
            <div className="flex flex-col items-center gap-1.5">
              <div
                className={cn(
                  'flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                  current ? 'text-walnut-foreground' : done ? 'text-white' : 'text-muted',
                )}
                style={{
                  backgroundColor: current
                    ? 'var(--walnut)'
                    : done
                      ? 'var(--status-success)'
                      : 'var(--surface-2)',
                }}
              >
                {done ? <Check size={14} /> : i + 1}
              </div>
              <span
                className={cn(
                  'whitespace-nowrap text-[10px]',
                  current ? 'font-semibold text-fg' : 'text-muted',
                )}
                style={state === 'REWORK' && current ? { color: meta.color } : undefined}
              >
                {state === 'REWORK' && current ? 'Rework' : meta.label}
              </span>
            </div>
            {i < PATH.length - 1 && (
              <div
                className="mx-1.5 h-0.5 w-8 shrink-0 rounded"
                style={{ backgroundColor: i < currentIdx ? 'var(--status-success)' : 'var(--border)' }}
              />
            )}
          </div>
        )
      })}
    </div>
  )
}
