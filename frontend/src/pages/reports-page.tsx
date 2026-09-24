import { TrendingUp, Clock, CheckCircle2, AlertTriangle } from 'lucide-react'
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, LabelList } from 'recharts'
import { Card } from '@/components/ui/card'
import { useKpi } from '@/hooks/use-kpi'
import { STATUS_META, type OrderState } from '@/lib/status'

const pct = (v: number | null | undefined) => (v == null ? '—' : `${v}%`)
const days = (v: number | null | undefined) => (v == null ? '—' : `${v}d`)

const DELIVERY_COLOR: Record<string, string> = {
  assigned: 'var(--status-neutral)',
  out_for_delivery: 'var(--status-progress)',
  delivered: 'var(--status-success)',
}

const title = (s: string) => s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())

function ChartCard({ heading, children, empty }: { heading: string; children: React.ReactNode; empty: boolean }) {
  return (
    <Card className="space-y-3">
      <h2 className="font-display text-lg text-fg">{heading}</h2>
      {empty ? <p className="py-10 text-center text-sm text-muted">No data yet.</p> : children}
    </Card>
  )
}

export function ReportsPage() {
  const { scope, query } = useKpi()
  const { data, isLoading } = query

  const h = data?.headline
  const kpiCards = [
    { label: 'On-Time Delivery', value: pct(h?.on_time_rate ?? data?.on_time_rate), icon: CheckCircle2, tone: 'var(--status-success)' },
    { label: 'Avg Lead Time', value: days(h?.avg_lead_time_days ?? data?.avg_lead_time_days), icon: Clock, tone: 'var(--status-active)' },
    { label: 'OTE', value: pct(h?.ote ?? data?.ote), icon: TrendingUp, tone: 'var(--status-info)' },
    { label: 'Defect / Rework', value: pct(h?.defect_rate ?? data?.defect_rate), icon: AlertTriangle, tone: 'var(--status-alert)' },
  ]

  const orderData = Object.entries(data?.orders_by_status ?? {}).map(([s, c]) => ({
    name: STATUS_META[s as OrderState]?.label ?? s,
    value: c,
    color: STATUS_META[s as OrderState]?.color ?? 'var(--walnut)',
  }))
  const deliveryData = Object.entries(data?.deliveries_by_status ?? {}).map(([s, c]) => ({
    name: title(s),
    value: c,
    color: DELIVERY_COLOR[s] ?? 'var(--walnut)',
  }))
  const stageData = (data?.bottleneck?.breakdown ?? []).map((s) => ({ name: title(s.stage), minutes: s.avg_minutes, delayed: s.delayed }))

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div>
        <h1 className="font-display text-3xl text-fg">Reports</h1>
        <p className="mt-1 text-muted">A visual snapshot of where every order and delivery stands.</p>
      </div>

      {scope === 'none' ? (
        <Card className="text-center text-muted">You don't have access to reports.</Card>
      ) : isLoading || !data ? (
        <p className="p-8 text-center text-muted">Loading reports…</p>
      ) : (
        <>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {kpiCards.map(({ label, value, icon: Icon, tone }) => (
              <Card key={label} className="space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted">{label}</span>
                  <Icon size={18} style={{ color: tone }} />
                </div>
                <span className="font-display text-3xl text-fg">{value}</span>
              </Card>
            ))}
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <ChartCard heading="Orders by stage" empty={!orderData.length}>
              <ResponsiveContainer width="100%" height={280}>
                <PieChart>
                  <Pie data={orderData} dataKey="value" nameKey="name" innerRadius={55} outerRadius={95} paddingAngle={2} label={(e) => `${e.name}: ${e.value}`}>
                    {orderData.map((d) => <Cell key={d.name} fill={d.color} stroke="var(--surface)" />)}
                  </Pie>
                  <Tooltip contentStyle={{ background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 8, color: 'var(--fg)' }} />
                </PieChart>
              </ResponsiveContainer>
            </ChartCard>

            <ChartCard heading="Deliveries by status" empty={!deliveryData.length}>
              <ResponsiveContainer width="100%" height={280}>
                <PieChart>
                  <Pie data={deliveryData} dataKey="value" nameKey="name" outerRadius={95} paddingAngle={2} label={(e) => `${e.name}: ${e.value}`}>
                    {deliveryData.map((d) => <Cell key={d.name} fill={d.color} stroke="var(--surface)" />)}
                  </Pie>
                  <Tooltip contentStyle={{ background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 8, color: 'var(--fg)' }} />
                </PieChart>
              </ResponsiveContainer>
            </ChartCard>
          </div>

          <ChartCard heading="Average time per production stage (minutes)" empty={!stageData.length}>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={stageData} margin={{ top: 16, right: 8, left: 0, bottom: 0 }}>
                <XAxis dataKey="name" tick={{ fill: 'var(--muted)', fontSize: 12 }} axisLine={{ stroke: 'var(--border)' }} tickLine={false} />
                <YAxis tick={{ fill: 'var(--muted)', fontSize: 12 }} axisLine={{ stroke: 'var(--border)' }} tickLine={false} />
                <Tooltip cursor={{ fill: 'var(--surface-2)' }} contentStyle={{ background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 8, color: 'var(--fg)' }} />
                <Bar dataKey="minutes" radius={[6, 6, 0, 0]} fill="var(--walnut)">
                  <LabelList dataKey="minutes" position="top" fill="var(--muted)" fontSize={11} />
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </ChartCard>
        </>
      )}
    </div>
  )
}
