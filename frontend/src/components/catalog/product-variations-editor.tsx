import { useState } from 'react'
import { Plus, Trash2, Palette } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useSyncVariations } from '@/hooks/use-products'
import { apiError } from '@/lib/api-error'
import type { ProductVariation } from '@/lib/products-api'

type Row = { name: string; color_hex: string }

export function ProductVariationsEditor({ productId, initial }: { productId: number; initial: ProductVariation[] }) {
  const sync = useSyncVariations(productId)
  const [rows, setRows] = useState<Row[]>(
    initial.length ? initial.map((v) => ({ name: v.name, color_hex: v.color_hex })) : [],
  )
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  const add = () => setRows((r) => [...r, { name: '', color_hex: '#8a5a34' }])
  const remove = (i: number) => setRows((r) => r.filter((_, idx) => idx !== i))
  const set = (i: number, key: keyof Row, val: string) =>
    setRows((r) => r.map((row, idx) => (idx === i ? { ...row, [key]: val } : row)))

  const save = () => {
    setMessage(null)
    setError(null)
    const clean = rows.map((r) => ({ name: r.name.trim(), color_hex: r.color_hex })).filter((r) => r.name)
    sync.mutate(clean, {
      onSuccess: () => setMessage('Color variations saved.'),
      onError: (e) => setError(apiError(e)),
    })
  }

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <p className="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted"><Palette size={14} /> Color variations</p>
        <button onClick={add} className="inline-flex items-center gap-1 text-xs text-walnut hover:underline"><Plus size={13} /> Add color</button>
      </div>
      <p className="text-xs text-muted">Each color tints the 3D model so buyers can preview the finish.</p>

      {rows.length === 0 ? (
        <p className="text-sm text-muted">No colors yet — add the finishes this piece comes in.</p>
      ) : (
        <div className="space-y-2">
          {rows.map((row, i) => (
            <div key={i} className="flex items-center gap-2">
              <input
                type="color"
                value={row.color_hex}
                onChange={(e) => set(i, 'color_hex', e.target.value)}
                className="h-9 w-10 shrink-0 cursor-pointer rounded border border-border bg-bg"
                aria-label="Color"
              />
              <input
                value={row.name}
                onChange={(e) => set(i, 'name', e.target.value)}
                placeholder="e.g. Walnut"
                className="flex-1 rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
              />
              <button onClick={() => remove(i)} aria-label="Remove" className="rounded-full p-2 text-muted hover:text-[var(--status-danger)]"><Trash2 size={15} /></button>
            </div>
          ))}
        </div>
      )}

      {error && <p className="text-sm text-[var(--status-danger)]">{error}</p>}
      {message && <p className="text-sm text-[var(--status-success,#3f7d4a)]">{message}</p>}

      <Button size="sm" onClick={save} disabled={sync.isPending}>{sync.isPending ? 'Saving…' : 'Save colors'}</Button>
    </div>
  )
}
