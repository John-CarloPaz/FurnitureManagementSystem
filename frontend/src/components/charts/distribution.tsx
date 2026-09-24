export interface DistItem {
  label: string
  value: number
  color: string
}

/** Lightweight horizontal-bar distribution (theme-aware, no chart lib). */
export function DistributionBars({ items }: { items: DistItem[] }) {
  const total = items.reduce((s, i) => s + i.value, 0) || 1
  if (!items.length) return <p className="text-sm text-muted">No data yet.</p>

  return (
    <div className="space-y-2.5">
      {items.map((it) => (
        <div key={it.label} className="flex items-center gap-3 text-sm">
          <span className="w-36 shrink-0 truncate text-muted">{it.label}</span>
          <div className="h-2.5 flex-1 overflow-hidden rounded-full bg-surface-2">
            <div
              className="h-full rounded-full transition-all"
              style={{ width: `${(it.value / total) * 100}%`, backgroundColor: it.color }}
            />
          </div>
          <span className="w-8 text-right font-mono text-xs text-fg">{it.value}</span>
        </div>
      ))}
    </div>
  )
}
