import { TrendingUp, Clock, CheckCircle2, AlertTriangle, Sparkles } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { useKpi } from '@/hooks/use-kpi'
import { useBottleneckAdvice, useSchedule } from '@/hooks/use-dss'
import { useAuth } from '@/hooks/use-auth'
import { STATUS_META, type OrderState } from '@/lib/status'
import type { KpiData } from '@/lib/kpi-api'

const pct = (v: number | null | undefined) => (v == null ? '—' : `${v}%`)
const days = (v: number | null | undefined) => (v == null ? '—' : `${v}d`)

function KpiCards({ data }: { data: KpiData }) {
  const h = data.headline
  const cards = [
    { label: 'On-Time Delivery', value: pct(h?.on_time_rate ?? data.on_time_rate), icon: CheckCircle2, tone: 'var(--status-success)' },
    { label: 'Avg Lead Time', value: days(h?.avg_lead_time_days ?? data.avg_lead_time_days), icon: Clock, tone: 'var(--status-active)' },
    { label: 'OTE', value: pct(h?.ote ?? data.ote), icon: TrendingUp, tone: 'var(--status-info)' },
    { label: 'Defect / Rework', value: pct(h?.defect_rate ?? data.defect_rate), icon: AlertTriangle, tone: 'var(--status-alert)' },
  ]
  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {cards.map(({ label, value, icon: Icon, tone }) => (
        <Card key={label} className="space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-sm text-muted">{label}</span>
            <Icon size={18} style={{ color: tone }} />
          </div>
          <span className="font-display text-3xl text-fg">{value}</span>
        </Card>
      ))}
    </div>
  )
}

function StatusBreakdown({ counts }: { counts: Record<string, number> }) {
  const entries = Object.entries(counts)
  const total = entries.reduce((s, [, c]) => s + c, 0) || 1
  return (
    <Card className="space-y-3">
      <h2 className="font-display text-lg text-fg">Orders by status</h2>
      <div className="space-y-2">
        {entries.map(([status, count]) => {
          const meta = STATUS_META[status as OrderState]
          return (
            <div key={status} className="flex items-center gap-3 text-sm">
              <span className="w-36 shrink-0 text-muted">{meta?.label ?? status}</span>
              <div className="h-2 flex-1 overflow-hidden rounded-full bg-surface-2">
                <div className="h-full rounded-full" style={{ width: `${(count / total) * 100}%`, backgroundColor: meta?.color ?? 'var(--walnut)' }} />
              </div>
              <span className="w-8 text-right font-mono text-xs text-fg">{count}</span>
            </div>
          )
        })}
      </div>
    </Card>
  )
}

function BottleneckCard({ data }: { data: NonNullable<KpiData['bottleneck']> }) {
  const max = Math.max(...data.breakdown.map((s) => s.avg_minutes), 1)
  return (
    <Card className="space-y-3">
      <h2 className="font-display text-lg text-fg">Production bottleneck</h2>
      <p className="text-sm text-muted">
        Biggest hold-up: <span className="font-medium capitalize text-fg">{data.stage}</span>
        {data.delayed_count > 0 && ` · ${data.delayed_count} delayed`}
      </p>
      <div className="space-y-2">
        {data.breakdown.map((s) => (
          <div key={s.stage} className="flex items-center gap-3 text-sm">
            <span className="w-20 shrink-0 capitalize text-muted">{s.stage}</span>
            <div className="h-2 flex-1 overflow-hidden rounded-full bg-surface-2">
              <div
                className="h-full rounded-full"
                style={{ width: `${(s.avg_minutes / max) * 100}%`, backgroundColor: s.stage === data.stage ? 'var(--status-alert)' : 'var(--walnut)' }}
              />
            </div>
            <span className="w-16 text-right font-mono text-xs text-fg">{s.avg_minutes}m</span>
          </div>
        ))}
      </div>
    </Card>
  )
}

function ProductionDss() {
  const advice = useBottleneckAdvice(true)
  const schedule = useSchedule(true)
  const plan = schedule.data?.plan ?? []

  return (
    <Card className="space-y-4">
      <div className="flex items-center gap-2">
        <Sparkles size={18} style={{ color: 'var(--amber)' }} />
        <h2 className="font-display text-lg text-fg">Model-Driven DSS</h2>
      </div>

      {advice.data?.recommendation && (
        <p
          className="rounded-[var(--radius-sm)] px-3 py-2 text-sm text-fg"
          style={{ backgroundColor: 'color-mix(in srgb, var(--amber) 10%, transparent)' }}
        >
          {advice.data.recommendation}
        </p>
      )}

      <div className="space-y-2">
        <p className="text-xs font-medium uppercase tracking-wide text-muted">
          Suggested production order{schedule.data ? ` · ${schedule.data.strategy}` : ''}
        </p>
        {plan.length === 0 ? (
          <p className="text-sm text-muted">No confirmed orders awaiting production.</p>
        ) : (
          <ol className="space-y-1.5">
            {plan.map((j) => (
              <li key={j.order_item_id} className="flex items-center gap-3 text-sm">
                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-walnut text-xs font-semibold text-walnut-foreground">
                  {j.sequence}
                </span>
                <span className="flex-1 text-fg">{j.product_name}</span>
                <span className="font-mono text-xs text-muted">{j.order_number}</span>
                <span className="text-xs text-muted">due {j.due_at}</span>
              </li>
            ))}
          </ol>
        )}
      </div>
    </Card>
  )
}

export function AnalyticsPage() {
  const { scope, query } = useKpi()
  const { data, isLoading } = query
  const { has } = useAuth()
  const showDss = has('manufacturing.schedule')

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div>
        <h1 className="font-display text-3xl text-fg">Analytics</h1>
        <p className="mt-1 text-muted">Live KPIs across production and delivery.</p>
      </div>

      {scope === 'none' ? (
        <Card className="text-center text-muted">You don't have access to analytics.</Card>
      ) : isLoading || !data ? (
        <p className="p-8 text-center text-muted">Loading KPIs…</p>
      ) : (
        <>
          <KpiCards data={data} />
          {showDss && <ProductionDss />}
          <div className="grid gap-6 lg:grid-cols-2">
            {data.orders_by_status && <StatusBreakdown counts={data.orders_by_status} />}
            {data.bottleneck && <BottleneckCard data={data.bottleneck} />}
          </div>
        </>
      )}
    </div>
  )
}
