import { useState, type FormEvent, type ReactNode } from 'react'
import { Button } from '@/components/ui/button'
import { useCatalogOptions } from '@/hooks/use-products'
import type { Product, ProductInput } from '@/lib/products-api'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="space-y-1.5">
      <label className="text-sm font-medium text-fg">{label}</label>
      {children}
    </div>
  )
}

const num = (s: string): number | null => (s.trim() === '' ? null : Number(s))

export function ProductForm({
  initial,
  submitLabel,
  submitting,
  onSubmit,
  onCancel,
}: {
  initial?: Partial<Product>
  submitLabel: string
  submitting: boolean
  onSubmit: (data: ProductInput) => void
  onCancel?: () => void
}) {
  const { data: options } = useCatalogOptions()
  const [v, setV] = useState({
    name: initial?.name ?? '',
    category: initial?.category ?? '',
    material: initial?.material ?? '',
    wood_type: initial?.wood_type ?? '',
    finish: initial?.finish ?? '',
    base_price: initial?.base_price ?? '',
    lead_time_days: initial?.lead_time_days != null ? String(initial.lead_time_days) : '',
    width_cm: initial?.width_cm ?? '',
    depth_cm: initial?.depth_cm ?? '',
    height_cm: initial?.height_cm ?? '',
    weight_kg: initial?.weight_kg ?? '',
    description: initial?.description ?? '',
  })
  const set = (k: keyof typeof v, val: string) => setV((s) => ({ ...s, [k]: val }))

  const submit = (e: FormEvent) => {
    e.preventDefault()
    onSubmit({
      name: v.name,
      category: v.category || null,
      material: v.material || null,
      wood_type: v.wood_type || null,
      finish: v.finish || null,
      base_price: num(String(v.base_price)) ?? undefined,
      lead_time_days: num(v.lead_time_days),
      width_cm: num(String(v.width_cm)),
      depth_cm: num(String(v.depth_cm)),
      height_cm: num(String(v.height_cm)),
      weight_kg: num(String(v.weight_kg)),
      description: v.description || null,
    })
  }

  const dropdowns: { key: keyof typeof v; label: string; opts?: string[] }[] = [
    { key: 'category', label: 'Category', opts: options?.categories },
    { key: 'material', label: 'Material', opts: options?.materials },
    { key: 'wood_type', label: 'Wood type', opts: options?.wood_types },
    { key: 'finish', label: 'Finish', opts: options?.finishes },
  ]

  const dims: { key: keyof typeof v; label: string }[] = [
    { key: 'width_cm', label: 'Width (cm)' },
    { key: 'depth_cm', label: 'Depth (cm)' },
    { key: 'height_cm', label: 'Height (cm)' },
    { key: 'weight_kg', label: 'Weight (kg)' },
  ]

  return (
    <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
      <div className="sm:col-span-2">
        <Field label="Name">
          <input required value={v.name} onChange={(e) => set('name', e.target.value)} className={inputCls} />
        </Field>
      </div>

      {dropdowns.map(({ key, label, opts }) => (
        <Field key={key} label={label}>
          <select value={String(v[key])} onChange={(e) => set(key, e.target.value)} className={inputCls}>
            <option value="">Select {label.toLowerCase()}…</option>
            {opts?.map((o) => (
              <option key={o} value={o}>{o}</option>
            ))}
          </select>
        </Field>
      ))}

      <Field label="Base price (₱)">
        <input type="number" min="0" step="0.01" value={String(v.base_price)} onChange={(e) => set('base_price', e.target.value)} className={inputCls} />
      </Field>
      <Field label="Lead time (days)">
        <input type="number" min="0" value={v.lead_time_days} onChange={(e) => set('lead_time_days', e.target.value)} className={inputCls} />
      </Field>

      {dims.map(({ key, label }) => (
        <Field key={key} label={label}>
          <input type="number" min="0" step="0.1" value={String(v[key])} onChange={(e) => set(key, e.target.value)} className={inputCls} />
        </Field>
      ))}

      <div className="sm:col-span-2">
        <Field label="Description">
          <textarea rows={3} value={v.description} onChange={(e) => set('description', e.target.value)} className={inputCls} />
        </Field>
      </div>

      <div className="flex gap-2 sm:col-span-2">
        <Button type="submit" size="sm" disabled={submitting}>
          {submitting ? 'Saving…' : submitLabel}
        </Button>
        {onCancel && (
          <Button type="button" variant="ghost" size="sm" onClick={onCancel}>Cancel</Button>
        )}
      </div>
    </form>
  )
}
