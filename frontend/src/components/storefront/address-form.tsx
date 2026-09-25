import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { useBarangays, useCities, useProvinces } from '@/hooks/use-ph-address'
import type { AddressInput, DeliveryAddress } from '@/lib/addresses-api'
import type { PhOption } from '@/lib/ph-address-api'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)] disabled:opacity-50'
const labelCls = 'text-sm font-medium text-fg'

const emptyForm: AddressInput = {
  label: '',
  country: 'Philippines',
  province_code: null,
  province_name: '',
  city_code: null,
  city_name: '',
  barangay_code: null,
  barangay_name: '',
  street: '',
  landmark: '',
  notes: '',
  is_default: false,
}

function toForm(a: DeliveryAddress): AddressInput {
  return {
    label: a.label,
    country: a.country,
    province_code: a.province_code,
    province_name: a.province_name,
    city_code: a.city_code,
    city_name: a.city_name,
    barangay_code: a.barangay_code,
    barangay_name: a.barangay_name,
    street: a.street,
    landmark: a.landmark,
    notes: a.notes,
    is_default: a.is_default,
  }
}

const nameOf = (options: PhOption[] | undefined, code: string) =>
  options?.find((o) => o.code === code)?.name ?? ''

interface Props {
  initial?: DeliveryAddress
  submitLabel?: string
  submitting?: boolean
  onSubmit: (payload: AddressInput) => void
  onCancel?: () => void
}

/** Shopee/Lazada-style address form with cascading Province → City → Barangay dropdowns (PSGC). */
export function AddressForm({ initial, submitLabel = 'Save address', submitting, onSubmit, onCancel }: Props) {
  const [form, setForm] = useState<AddressInput>(initial ? toForm(initial) : emptyForm)
  const [error, setError] = useState<string | null>(null)

  const provinces = useProvinces()
  const cities = useCities(form.province_code ?? undefined)
  const barangays = useBarangays(form.city_code ?? undefined)

  const set = <K extends keyof AddressInput>(k: K, v: AddressInput[K]) =>
    setForm((f) => ({ ...f, [k]: v }))

  const onProvince = (code: string) =>
    setForm((f) => ({
      ...f,
      province_code: code || null,
      province_name: nameOf(provinces.data, code),
      city_code: null,
      city_name: '',
      barangay_code: null,
      barangay_name: '',
    }))

  const onCity = (code: string) =>
    setForm((f) => ({
      ...f,
      city_code: code || null,
      city_name: nameOf(cities.data, code),
      barangay_code: null,
      barangay_name: '',
    }))

  const onBarangay = (code: string) =>
    setForm((f) => ({ ...f, barangay_code: code || null, barangay_name: nameOf(barangays.data, code) }))

  const submit = () => {
    if (!form.province_name || !form.city_name || !form.barangay_name || !form.street.trim()) {
      setError('Please complete province, city, barangay and street.')
      return
    }
    setError(null)
    onSubmit({ ...form, label: form.label?.trim() || null })
  }

  return (
    <div className="space-y-4">
      <div className="space-y-1.5">
        <label className={labelCls} htmlFor="addr-label">Label (optional)</label>
        <input id="addr-label" value={form.label ?? ''} onChange={(e) => set('label', e.target.value)} placeholder="Home, Office…" className={inputCls} />
      </div>

      <div className="space-y-1.5">
        <label className={labelCls} htmlFor="addr-country">Country</label>
        <select id="addr-country" value={form.country} onChange={(e) => set('country', e.target.value)} className={inputCls}>
          <option value="Philippines">Philippines</option>
        </select>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <label className={labelCls} htmlFor="addr-province">Province</label>
          <select
            id="addr-province"
            value={form.province_code ?? ''}
            onChange={(e) => onProvince(e.target.value)}
            disabled={provinces.isLoading}
            className={inputCls}
          >
            <option value="">{provinces.isLoading ? 'Loading…' : 'Select province'}</option>
            {provinces.data?.map((p) => <option key={p.code} value={p.code}>{p.name}</option>)}
          </select>
        </div>

        <div className="space-y-1.5">
          <label className={labelCls} htmlFor="addr-city">City / Municipality</label>
          <select
            id="addr-city"
            value={form.city_code ?? ''}
            onChange={(e) => onCity(e.target.value)}
            disabled={!form.province_code || cities.isLoading}
            className={inputCls}
          >
            <option value="">{cities.isLoading ? 'Loading…' : 'Select city / municipality'}</option>
            {cities.data?.map((c) => <option key={c.code} value={c.code}>{c.name}</option>)}
          </select>
        </div>

        <div className="space-y-1.5">
          <label className={labelCls} htmlFor="addr-barangay">Barangay</label>
          <select
            id="addr-barangay"
            value={form.barangay_code ?? ''}
            onChange={(e) => onBarangay(e.target.value)}
            disabled={!form.city_code || barangays.isLoading}
            className={inputCls}
          >
            <option value="">{barangays.isLoading ? 'Loading…' : 'Select barangay'}</option>
            {barangays.data?.map((b) => <option key={b.code} value={b.code}>{b.name}</option>)}
          </select>
        </div>

        <div className="space-y-1.5">
          <label className={labelCls} htmlFor="addr-street">Street name / House no.</label>
          <input id="addr-street" value={form.street} onChange={(e) => set('street', e.target.value)} placeholder="123 Mango Ave." className={inputCls} />
        </div>
      </div>

      <div className="space-y-1.5">
        <label className={labelCls} htmlFor="addr-landmark">Landmark (optional)</label>
        <input id="addr-landmark" value={form.landmark ?? ''} onChange={(e) => set('landmark', e.target.value)} placeholder="Near the church, blue gate…" className={inputCls} />
      </div>

      <div className="space-y-1.5">
        <label className={labelCls} htmlFor="addr-notes">Note to the driver (optional)</label>
        <textarea id="addr-notes" rows={2} value={form.notes ?? ''} onChange={(e) => set('notes', e.target.value)} placeholder="Call on arrival, leave at the gate…" className={inputCls} />
      </div>

      <label className="flex items-center gap-2 text-sm text-fg">
        <input type="checkbox" checked={!!form.is_default} onChange={(e) => set('is_default', e.target.checked)} className="rounded border-border" />
        Set as my default address
      </label>

      {error && <p className="text-sm text-[var(--status-danger)]">{error}</p>}

      <div className="flex gap-2">
        <Button size="pill" onClick={submit} disabled={submitting}>
          {submitting ? 'Saving…' : submitLabel}
        </Button>
        {onCancel && (
          <button type="button" onClick={onCancel} className="rounded-full border border-border px-4 py-1.5 text-sm text-fg hover:bg-surface-2">
            Cancel
          </button>
        )}
      </div>
    </div>
  )
}
