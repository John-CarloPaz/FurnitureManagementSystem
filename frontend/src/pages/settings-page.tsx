import { useEffect, useState } from 'react'
import { SlidersHorizontal } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useAdminSettings, useUpdateSettings } from '@/hooks/use-settings'
import { apiError } from '@/lib/api-error'
import { peso } from '@/lib/status'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

export function SettingsPage() {
  const { data, isLoading } = useAdminSettings()
  const update = useUpdateSettings()
  const [shipping, setShipping] = useState('')
  const [vatPct, setVatPct] = useState('')
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (data) {
      setShipping(String(data.shipping_fee))
      setVatPct(String(Number((data.vat_rate * 100).toFixed(2))))
    }
  }, [data])

  const save = () => {
    setMessage(null)
    setError(null)
    const shipping_fee = Number(shipping)
    const vat_rate = Number(vatPct) / 100
    if (Number.isNaN(shipping_fee) || Number.isNaN(vat_rate) || shipping_fee < 0 || vat_rate < 0 || vat_rate > 1) {
      setError('Enter a valid shipping fee and a tax rate between 0 and 100%.')
      return
    }
    update.mutate({ shipping_fee, vat_rate }, {
      onSuccess: () => setMessage('Settings saved — they apply to new orders right away.'),
      onError: (e) => setError(apiError(e)),
    })
  }

  const sample = 1000
  const sh = Number(shipping) || 0
  const tax = Math.round(sample * ((Number(vatPct) || 0) / 100) * 100) / 100

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 className="flex items-center gap-2 font-display text-2xl text-fg"><SlidersHorizontal size={22} /> Shop Settings</h1>
        <p className="text-sm text-muted">The shipping fee and tax applied to every order. Super-admin only.</p>
      </div>

      {isLoading ? (
        <p className="py-10 text-center text-muted">Loading…</p>
      ) : (
        <Card className="space-y-4">
          {error && <p className="rounded-[var(--radius-sm)] px-3 py-2 text-sm" style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}>{error}</p>}
          {message && <p className="rounded-[var(--radius-sm)] px-3 py-2 text-sm" style={{ backgroundColor: 'color-mix(in srgb, var(--status-success) 12%, transparent)', color: 'var(--status-success)' }}>{message}</p>}

          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="shipping">Shipping fee (₱)</label>
            <input id="shipping" type="number" min="0" step="0.01" value={shipping} onChange={(e) => setShipping(e.target.value)} className={inputCls} />
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="vat">Tax / VAT rate (%)</label>
            <input id="vat" type="number" min="0" max="100" step="0.1" value={vatPct} onChange={(e) => setVatPct(e.target.value)} className={inputCls} />
            <p className="text-xs text-muted">Applied to the subtotal after any voucher discount.</p>
          </div>

          <div className="rounded-[var(--radius-sm)] border border-border bg-surface-2 p-3 text-sm text-muted">
            Example — a {peso(sample)} order: shipping <span className="text-fg">{peso(sh)}</span> + VAT <span className="text-fg">{peso(tax)}</span> → total <span className="font-medium text-fg">{peso(sample + sh + tax)}</span>
          </div>

          <Button size="pill" onClick={save} disabled={update.isPending}>
            {update.isPending ? 'Saving…' : 'Save settings'}
          </Button>
        </Card>
      )}
    </div>
  )
}
