import { STATUS_META, type OrderState } from '@/lib/status'

/** Color-coded FSM status pill — soft tint + solid dot + label. */
export function StatusPill({ state }: { state: OrderState }) {
  const meta = STATUS_META[state]
  return (
    <span
      className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
      style={{ backgroundColor: `color-mix(in srgb, ${meta.color} 14%, transparent)`, color: meta.color }}
    >
      <span className="h-1.5 w-1.5 rounded-full" style={{ backgroundColor: meta.color }} />
      {meta.label}
    </span>
  )
}
