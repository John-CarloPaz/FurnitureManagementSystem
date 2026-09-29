import { useState } from 'react'
import { Plus, Ticket, Pencil, Trash2 } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/use-auth'
import { useCreateVoucher, useDeleteVoucher, useUpdateVoucher, useVouchers } from '@/hooks/use-vouchers'
import { apiError } from '@/lib/api-error'
import { peso } from '@/lib/status'
import type { Voucher, VoucherInput } from '@/lib/vouchers-api'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

const blank: VoucherInput = { code: '', type: 'percent', value: 10, min_spend: null, max_discount: null, expires_at: null, usage_limit: null, is_active: true, description: '' }

function toInput(v: Voucher): VoucherInput {
  return {
    code: v.code, type: v.type, value: Number(v.value),
    min_spend: v.min_spend ? Number(v.min_spend) : null,
    max_discount: v.max_discount ? Number(v.max_discount) : null,
    expires_at: v.expires_at ? v.expires_at.slice(0, 10) : null,
    usage_limit: v.usage_limit, is_active: v.is_active, description: v.description ?? '',
  }
}

export function VouchersPage() {
  const { has } = useAuth()
  const canManage = has('vouchers.create')
  const { data: vouchers = [], isLoading } = useVouchers()
  const create = useCreateVoucher()
  const update = useUpdateVoucher()
  const remove = useDeleteVoucher()

  const [editing, setEditing] = useState<Voucher | 'new' | null>(null)
  const [form, setForm] = useState<VoucherInput>(blank)
  const [error, setError] = useState<string | null>(null)

  const open = (v: Voucher | 'new') => {
    setEditing(v)
    setForm(v === 'new' ? blank : toInput(v))
    setError(null)
  }
  const close = () => { setEditing(null); setError(null) }
  const set = <K extends keyof VoucherInput>(k: K, v: VoucherInput[K]) => setForm((f) => ({ ...f, [k]: v }))

  const submit = () => {
    setError(null)
    const payload: VoucherInput = { ...form, value: Number(form.value) }
    const onErr = (e: unknown) => setError(apiError(e))
    if (editing !== 'new' && editing) update.mutate({ id: editing.id, payload }, { onSuccess: close, onError: onErr })
    else create.mutate(payload, { onSuccess: close, onError: onErr })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="flex items-center gap-2 font-display text-2xl text-fg"><Ticket size={22} /> Vouchers</h1>
          <p className="text-sm text-muted">Discount codes customers can apply at checkout.</p>
        </div>
        {canManage && editing === null && <Button size="pill" onClick={() => open('new')}><Plus size={16} /> New voucher</Button>}
      </div>

      {editing !== null && (
        <Card className="space-y-4">
          <h2 className="font-display text-lg text-fg">{editing === 'new' ? 'New voucher' : `Edit ${editing.code}`}</h2>
          {error && <p className="rounded-[var(--radius-sm)] px-3 py-2 text-sm" style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}>{error}</p>}
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5"><label className="text-sm font-medium text-fg">Code</label><input value={form.code} onChange={(e) => set('code', e.target.value.toUpperCase())} placeholder="SAVE10" className={inputCls} /></div>
            <div className="space-y-1.5"><label className="text-sm font-medium text-fg">Type</label>
              <select value={form.type} onChange={(e) => set('type', e.target.value as 'percent' | 'fixed')} className={inputCls}>
                <option value="percent">Percentage (%)</option>
                <option value="fixed">Fixed amount (₱)</option>
              </select>
            </div>
            <div className="space-y-1.5"><label className="text-sm font-medium text-fg">{form.type === 'percent' ? 'Percent off' : 'Amount off (₱)'}</label><input type="number" min="0" value={form.value} onChange={(e) => set('value', Number(e.target.value))} className={inputCls} /></div>
            <div className="space-y-1.5"><label className="text-sm font-medium text-fg">Min. spend (₱, optional)</label><input type="number" min="0" value={form.min_spend ?? ''} onChange={(e) => set('min_spend', e.target.value ? Number(e.target.value) : null)} className={inputCls} /></div>
            {form.type === 'percent' && <div className="space-y-1.5"><label className="text-sm font-medium text-fg">Max discount cap (₱, optional)</label><input type="number" min="0" value={form.max_discount ?? ''} onChange={(e) => set('max_discount', e.target.value ? Number(e.target.value) : null)} className={inputCls} /></div>}
            <div className="space-y-1.5"><label className="text-sm font-medium text-fg">Usage limit (optional)</label><input type="number" min="1" value={form.usage_limit ?? ''} onChange={(e) => set('usage_limit', e.target.value ? Number(e.target.value) : null)} className={inputCls} /></div>
            <div className="space-y-1.5"><label className="text-sm font-medium text-fg">Expires (optional)</label><input type="date" value={form.expires_at ?? ''} onChange={(e) => set('expires_at', e.target.value || null)} className={inputCls} /></div>
            <div className="space-y-1.5 sm:col-span-2"><label className="text-sm font-medium text-fg">Description (optional)</label><input value={form.description ?? ''} onChange={(e) => set('description', e.target.value)} className={inputCls} /></div>
          </div>
          <label className="flex items-center gap-2 text-sm text-fg"><input type="checkbox" checked={!!form.is_active} onChange={(e) => set('is_active', e.target.checked)} /> Active</label>
          <div className="flex gap-2">
            <Button size="pill" onClick={submit} disabled={create.isPending || update.isPending}>{editing === 'new' ? 'Create' : 'Save'}</Button>
            <button onClick={close} className="rounded-full border border-border px-4 py-1.5 text-sm text-fg hover:bg-surface-2">Cancel</button>
          </div>
        </Card>
      )}

      {isLoading ? (
        <p className="py-10 text-center text-muted">Loading…</p>
      ) : !vouchers.length ? (
        <Card className="text-center text-muted">No vouchers yet.</Card>
      ) : (
        <Card className="overflow-x-auto p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border text-left text-xs uppercase tracking-wide text-muted">
                <th className="px-4 py-3">Code</th><th className="px-4 py-3">Discount</th><th className="px-4 py-3">Min spend</th><th className="px-4 py-3">Used</th><th className="px-4 py-3">Status</th><th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              {vouchers.map((v) => (
                <tr key={v.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3"><span className="font-mono font-medium text-fg">{v.code}</span>{v.description && <p className="text-xs text-muted">{v.description}</p>}</td>
                  <td className="px-4 py-3 text-fg">{v.type === 'percent' ? `${Number(v.value)}%` : peso(v.value)}{v.max_discount && v.type === 'percent' ? ` (max ${peso(v.max_discount)})` : ''}</td>
                  <td className="px-4 py-3 text-muted">{v.min_spend ? peso(v.min_spend) : '—'}</td>
                  <td className="px-4 py-3 text-muted">{v.used_count}{v.usage_limit ? ` / ${v.usage_limit}` : ''}</td>
                  <td className="px-4 py-3">
                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${v.is_active ? 'bg-[var(--amber-soft)] text-walnut' : 'bg-surface-2 text-muted'}`}>{v.is_active ? 'Active' : 'Inactive'}</span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    {canManage && (
                      <div className="flex justify-end gap-1">
                        <button onClick={() => open(v)} aria-label="Edit" className="rounded-full p-2 text-muted hover:bg-surface-2 hover:text-fg"><Pencil size={15} /></button>
                        <button onClick={() => remove.mutate(v.id)} aria-label="Delete" className="rounded-full p-2 text-muted hover:bg-surface-2 hover:text-[var(--status-danger)]"><Trash2 size={15} /></button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </Card>
      )}
    </div>
  )
}
