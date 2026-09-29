import { useEffect, useMemo, useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { Trash2, ArrowLeft, Plus, MapPin, Tag, X } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { AddressForm } from '@/components/storefront/address-form'
import { useCart } from '@/stores/cart'
import { usePlaceOrder } from '@/hooks/use-orders'
import { useAddresses, useCreateAddress } from '@/hooks/use-addresses'
import { useShopSettings } from '@/hooks/use-storefront'
import { useAuth } from '@/hooks/use-auth'
import { peso } from '@/lib/status'
import { previewVoucher } from '@/lib/vouchers-api'
import type { DeliveryAddress } from '@/lib/addresses-api'

const PAYMENTS = [
  { value: 'COD', label: 'Cash on Delivery', hint: 'Pay when your order arrives.' },
  { value: 'GCASH', label: 'GCash', hint: "You'll get payment instructions after placing the order." },
  { value: 'BANK', label: 'Bank Transfer', hint: "You'll get bank details after placing the order." },
]

export function StorefrontCart() {
  const navigate = useNavigate()
  const { isAuthenticated } = useAuth()
  const { items, setQty, remove, clear, total } = useCart()
  const place = usePlaceOrder()
  const { data: addresses = [] } = useAddresses()
  const createAddress = useCreateAddress()
  const { data: settings } = useShopSettings()

  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [showNew, setShowNew] = useState(false)
  const [notes, setNotes] = useState('')
  const [payment, setPayment] = useState('COD')

  const [voucherInput, setVoucherInput] = useState('')
  const [voucher, setVoucher] = useState<{ code: string; discount: number } | null>(null)
  const [voucherError, setVoucherError] = useState<string | null>(null)
  const [checking, setChecking] = useState(false)

  const subtotal = total()
  const shipping = settings?.shipping_fee ?? 0
  const vatRate = settings?.vat_rate ?? 0
  const discount = voucher?.discount ?? 0
  const taxable = Math.max(0, subtotal - discount)
  const tax = Math.round(taxable * vatRate * 100) / 100
  const grandTotal = taxable + shipping + tax

  // A voucher validated against an old subtotal shouldn't linger if the cart changes.
  useEffect(() => { setVoucher(null); setVoucherError(null) }, [subtotal])

  const activeId = useMemo(() => {
    if (selectedId !== null) return selectedId
    return addresses.find((a) => a.is_default)?.id ?? addresses[0]?.id ?? null
  }, [selectedId, addresses])
  const selected: DeliveryAddress | undefined = addresses.find((a) => a.id === activeId)

  const applyVoucher = async () => {
    const code = voucherInput.trim()
    if (!code) return
    setChecking(true)
    setVoucherError(null)
    try {
      const res = await previewVoucher(code, subtotal)
      if (res.valid && res.code) setVoucher({ code: res.code, discount: res.discount })
      else { setVoucher(null); setVoucherError(res.message ?? 'That code cannot be applied.') }
    } catch {
      setVoucherError('Could not check that code. Try again.')
    } finally {
      setChecking(false)
    }
  }

  const onPlace = () => {
    if (!selected) return
    const driverNote = selected.notes ? `Note to driver: ${selected.notes}` : ''
    const combinedNotes = [notes.trim(), driverNote].filter(Boolean).join(' — ') || undefined
    place.mutate(
      {
        items: items.map((i) => ({ product_id: i.product.id, quantity: i.quantity })),
        deliveryAddress: selected.formatted,
        notes: combinedNotes,
        voucherCode: voucher?.code,
        paymentMethod: payment,
      },
      { onSuccess: (o) => { clear(); navigate(`/shop/orders/${o.id}`) } },
    )
  }

  if (!items.length) {
    return (
      <div className="mx-auto max-w-3xl space-y-4">
        <h1 className="font-display text-3xl text-fg">Your cart</h1>
        <Card className="text-center text-muted">
          Nothing here yet. <Link to="/shop" className="text-walnut">Browse the collection</Link>.
        </Card>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <Link to="/shop" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
        <ArrowLeft size={15} /> Continue shopping
      </Link>
      <h1 className="font-display text-3xl text-fg">Your cart</h1>

      <Card className="p-0">
        <table className="w-full text-sm">
          <tbody>
            {items.map(({ product, quantity }) => (
              <tr key={product.id} className="border-b border-border last:border-0">
                <td className="px-5 py-3">
                  <p className="text-fg">{product.name}</p>
                  <p className="text-xs text-muted">{peso(product.base_price)} each</p>
                </td>
                <td className="px-5 py-3">
                  <input type="number" min="1" value={quantity} onChange={(e) => setQty(product.id, Number(e.target.value))} className="w-16 rounded-[var(--radius-sm)] border border-border bg-bg px-2 py-1 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]" />
                </td>
                <td className="px-5 py-3 text-right font-mono text-fg">{peso(parseFloat(product.base_price) * quantity)}</td>
                <td className="px-5 py-3 text-right">
                  <button onClick={() => remove(product.id)} className="text-muted hover:text-[var(--status-danger)]"><Trash2 size={16} /></button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>

      {!isAuthenticated ? (
        <Card className="space-y-3 rounded-[var(--radius-sm)] border border-border bg-surface-2 p-4 text-center">
          <p className="text-sm text-fg">Sign in to place your order and track it.</p>
          <div className="flex justify-center gap-2">
            <Link to="/login?next=/shop/cart" className="rounded-full border border-border px-4 py-1.5 text-sm text-fg hover:bg-surface">Sign in</Link>
            <Link to="/register?next=/shop/cart" className="rounded-full bg-walnut px-4 py-1.5 text-sm font-medium text-walnut-foreground hover:bg-walnut-hover">Create account</Link>
          </div>
        </Card>
      ) : (
        <>
          {/* Delivery address */}
          <Card className="space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="flex items-center gap-2 font-display text-lg text-fg"><MapPin size={17} /> Delivery address</h2>
              {!showNew && (
                <button onClick={() => setShowNew(true)} className="inline-flex items-center gap-1 text-sm text-walnut hover:underline">
                  <Plus size={14} /> New address
                </button>
              )}
            </div>
            {addresses.length > 0 && !showNew && (
              <div className="space-y-2">
                {addresses.map((a) => (
                  <label key={a.id} className={`flex cursor-pointer items-start gap-3 rounded-[var(--radius-sm)] border p-3 transition-colors ${activeId === a.id ? 'border-walnut bg-[var(--amber-soft)]' : 'border-border hover:bg-surface-2'}`}>
                    <input type="radio" name="address" checked={activeId === a.id} onChange={() => setSelectedId(a.id)} className="mt-1" />
                    <span className="space-y-0.5">
                      <span className="block text-sm font-medium text-fg">{a.label || 'Address'}{a.is_default && <span className="ml-2 text-[10px] uppercase tracking-wide text-walnut">Default</span>}</span>
                      <span className="block text-sm text-muted">{a.formatted}</span>
                    </span>
                  </label>
                ))}
              </div>
            )}
            {(showNew || addresses.length === 0) && (
              <div className="rounded-[var(--radius-sm)] border border-border p-4">
                <AddressForm
                  submitLabel="Save & use this address"
                  submitting={createAddress.isPending}
                  onSubmit={(payload) =>
                    createAddress.mutate(
                      { ...payload, is_default: payload.is_default || addresses.length === 0 },
                      { onSuccess: (addr) => { setSelectedId(addr.id); setShowNew(false) } },
                    )
                  }
                  onCancel={addresses.length ? () => setShowNew(false) : undefined}
                />
              </div>
            )}
          </Card>

          {/* Payment method */}
          <Card className="space-y-3">
            <h2 className="font-display text-lg text-fg">Payment method</h2>
            <div className="grid gap-2 sm:grid-cols-3">
              {PAYMENTS.map((p) => (
                <label key={p.value} className={`cursor-pointer rounded-[var(--radius-sm)] border p-3 transition-colors ${payment === p.value ? 'border-walnut bg-[var(--amber-soft)]' : 'border-border hover:bg-surface-2'}`}>
                  <span className="flex items-center gap-2">
                    <input type="radio" name="payment" checked={payment === p.value} onChange={() => setPayment(p.value)} />
                    <span className="text-sm font-medium text-fg">{p.label}</span>
                  </span>
                  <span className="mt-1 block text-xs text-muted">{p.hint}</span>
                </label>
              ))}
            </div>
          </Card>

          {/* Voucher + summary */}
          <Card className="space-y-4">
            <div className="space-y-1.5">
              <label className="flex items-center gap-2 text-sm font-medium text-fg"><Tag size={15} /> Voucher code</label>
              {voucher ? (
                <div className="flex items-center justify-between rounded-[var(--radius-sm)] border border-walnut bg-[var(--amber-soft)] px-3 py-2 text-sm">
                  <span className="text-fg"><b>{voucher.code}</b> applied — −{peso(voucher.discount)}</span>
                  <button onClick={() => { setVoucher(null); setVoucherInput('') }} aria-label="Remove voucher" className="text-muted hover:text-fg"><X size={15} /></button>
                </div>
              ) : (
                <div className="flex gap-2">
                  <input
                    value={voucherInput}
                    onChange={(e) => setVoucherInput(e.target.value.toUpperCase())}
                    placeholder="e.g. SAVE10"
                    className="flex-1 rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
                  />
                  <button onClick={applyVoucher} disabled={checking || !voucherInput.trim()} className="rounded-full border border-walnut px-4 py-1.5 text-sm font-medium text-walnut hover:bg-[var(--amber-soft)] disabled:opacity-50">
                    {checking ? 'Checking…' : 'Apply'}
                  </button>
                </div>
              )}
              {voucherError && <p className="text-sm text-[var(--status-danger)]">{voucherError}</p>}
            </div>

            <div className="space-y-1.5 border-t border-border pt-3 text-sm">
              <div className="flex justify-between text-muted"><span>Subtotal</span><span className="font-mono text-fg">{peso(subtotal)}</span></div>
              {discount > 0 && <div className="flex justify-between text-muted"><span>Discount</span><span className="font-mono text-[var(--status-success,#3f7d4a)]">−{peso(discount)}</span></div>}
              <div className="flex justify-between text-muted"><span>Shipping fee</span><span className="font-mono text-fg">{peso(shipping)}</span></div>
              <div className="flex justify-between text-muted"><span>VAT ({Math.round(vatRate * 100)}%)</span><span className="font-mono text-fg">{peso(tax)}</span></div>
              <div className="flex justify-between border-t border-border pt-2 text-base">
                <span className="font-medium text-fg">Total</span>
                <span className="font-display text-xl text-fg">{peso(grandTotal)}</span>
              </div>
            </div>

            <div className="space-y-1.5">
              <label className="text-sm font-medium text-fg">Order notes (optional)</label>
              <input value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Anything else we should know?" className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]" />
            </div>

            <Button className="w-full" size="pill" onClick={onPlace} disabled={place.isPending || !selected}>
              {place.isPending ? 'Placing order…' : !selected ? 'Add a delivery address' : 'Place order'}
            </Button>
            {place.isError && <p className="text-sm text-[var(--status-danger)]">Couldn't place the order. Please try again.</p>}
          </Card>
        </>
      )}
    </div>
  )
}
