import { useState } from 'react'
import { Plus, Trash2, Palette, Sparkles, Upload, Check, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useGenerateVariationModel, useSyncVariations, useUploadVariationModel } from '@/hooks/use-products'
import { apiError } from '@/lib/api-error'
import type { ProductVariation } from '@/lib/products-api'

type Row = { id?: number; name: string; color_hex: string }

export function ProductVariationsEditor({ productId, initial }: { productId: number; initial: ProductVariation[] }) {
  const sync = useSyncVariations(productId)
  const uploadModel = useUploadVariationModel(productId)
  const generate = useGenerateVariationModel(productId)

  const [rows, setRows] = useState<Row[]>(initial.map((v) => ({ id: v.id, name: v.name, color_hex: v.color_hex })))
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [busyId, setBusyId] = useState<number | null>(null)

  const byId = new Map(initial.map((v) => [v.id, v]))

  const add = () => setRows((r) => [...r, { name: '', color_hex: '#8a5a34' }])
  const remove = (i: number) => setRows((r) => r.filter((_, idx) => idx !== i))
  const set = (i: number, key: keyof Row, val: string) =>
    setRows((r) => r.map((row, idx) => (idx === i ? { ...row, [key]: val } : row)))

  const save = () => {
    setMessage(null)
    setError(null)
    const clean = rows.map((r) => ({ id: r.id, name: r.name.trim(), color_hex: r.color_hex })).filter((r) => r.name)
    sync.mutate(clean, {
      onSuccess: (res) => {
        setRows(res.map((v) => ({ id: v.id, name: v.name, color_hex: v.color_hex })))
        setMessage('Color variations saved.')
      },
      onError: (e) => setError(apiError(e)),
    })
  }

  const onUpload = (variationId: number, file?: File) => {
    if (!file) return
    setBusyId(variationId)
    uploadModel.mutate({ variationId, file }, { onError: (e) => setError(apiError(e)), onSettled: () => setBusyId(null) })
  }
  const onGenerate = (variationId: number, image?: File) => {
    if (!image) return
    setBusyId(variationId)
    generate.mutate({ variationId, image }, { onError: (e) => setError(apiError(e)), onSettled: () => setBusyId(null) })
  }

  const status = (sv?: ProductVariation) => {
    if (!sv) return null
    const g = sv.generation
    if (g && (g.status === 'pending' || g.status === 'processing')) {
      return <span className="flex items-center gap-1 text-xs text-[var(--status-active)]"><Loader2 size={12} className="animate-spin" /> Generating… {g.progress}%</span>
    }
    if (g && g.status === 'failed') return <span className="text-xs text-[var(--status-danger)]">Generation failed</span>
    if (sv.has_model) return <span className="flex items-center gap-1 text-xs text-[var(--status-success,#3f7d4a)]"><Check size={12} /> 3D ready</span>
    return <span className="text-xs text-muted">No 3D yet</span>
  }

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <p className="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted"><Palette size={14} /> Color variations</p>
        <button onClick={add} className="inline-flex items-center gap-1 text-xs text-walnut hover:underline"><Plus size={13} /> Add color</button>
      </div>
      <p className="text-xs text-muted">Each color can have its own photo + 3D model (buyers see them when they pick that color). Save colors first, then add a 3D model per color.</p>

      {rows.length === 0 ? (
        <p className="text-sm text-muted">No colors yet — add the finishes this piece comes in.</p>
      ) : (
        <div className="space-y-2.5">
          {rows.map((row, i) => {
            const sv = row.id ? byId.get(row.id) : undefined
            const working = busyId != null && busyId === row.id
            return (
              <div key={row.id ?? `new-${i}`} className="rounded-[var(--radius-sm)] border border-border p-2.5">
                <div className="flex items-center gap-2">
                  <input type="color" value={row.color_hex} onChange={(e) => set(i, 'color_hex', e.target.value)} className="h-9 w-10 shrink-0 cursor-pointer rounded border border-border bg-bg" aria-label="Color" />
                  <input value={row.name} onChange={(e) => set(i, 'name', e.target.value)} placeholder="e.g. Walnut" className="flex-1 rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]" />
                  <button onClick={() => remove(i)} aria-label="Remove" className="rounded-full p-2 text-muted hover:text-[var(--status-danger)]"><Trash2 size={15} /></button>
                </div>

                {row.id ? (
                  <div className="mt-2 flex flex-wrap items-center gap-2 pl-12">
                    {working ? (
                      <span className="flex items-center gap-1 text-xs text-muted"><Loader2 size={12} className="animate-spin" /> Working…</span>
                    ) : (
                      status(sv)
                    )}
                    <span className="flex-1" />
                    <label className="inline-flex cursor-pointer items-center gap-1 rounded-full border border-border px-2.5 py-1 text-xs text-fg hover:bg-surface-2">
                      <Sparkles size={12} /> Generate from photo
                      <input type="file" accept="image/*" className="hidden" onChange={(e) => onGenerate(row.id!, e.target.files?.[0])} />
                    </label>
                    <label className="inline-flex cursor-pointer items-center gap-1 rounded-full border border-border px-2.5 py-1 text-xs text-fg hover:bg-surface-2">
                      <Upload size={12} /> Upload .glb
                      <input type="file" accept=".glb,.gltf,.obj" className="hidden" onChange={(e) => onUpload(row.id!, e.target.files?.[0])} />
                    </label>
                  </div>
                ) : (
                  <p className="mt-1.5 pl-12 text-xs text-muted">Save colors to add a 3D model for this finish.</p>
                )}
              </div>
            )
          })}
        </div>
      )}

      {error && <p className="text-sm text-[var(--status-danger)]">{error}</p>}
      {message && <p className="text-sm text-[var(--status-success,#3f7d4a)]">{message}</p>}

      <Button size="sm" onClick={save} disabled={sync.isPending}>{sync.isPending ? 'Saving…' : 'Save colors'}</Button>
    </div>
  )
}
