import { useMemo, useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { Trash2, ArrowLeft, Plus, MapPin } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { AddressForm } from '@/components/storefront/address-form'
import { useCart } from '@/stores/cart'
import { usePlaceOrder } from '@/hooks/use-orders'
import { useAddresses, useCreateAddress } from '@/hooks/use-addresses'
import { useAuth } from '@/hooks/use-auth'
import { peso } from '@/lib/status'
import type { DeliveryAddress } from '@/lib/addresses-api'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

export function StorefrontCart() {
  const navigate = useNavigate()
  const { isAuthenticated } = useAuth()
  const { items, setQty, remove, clear, total } = useCart()
  const place = usePlaceOrder()
  const { data: addresses = [] } = useAddresses()
  const createAddress = useCreateAddress()

  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [showNew, setShowNew] = useState(false)
  const [notes, setNotes] = useState('')

  // Default to the customer's default address (or the first one) once they load.
  const activeId = useMemo(() => {
    if (selectedId !== null) return selectedId
    return addresses.find((a) => a.is_default)?.id ?? addresses[0]?.id ?? null
  }, [selectedId, addresses])

  const selected: DeliveryAddress | undefined = addresses.find((a) => a.id === activeId)

  const onPlace = () => {
    if (!selected) return
    const driverNote = selected.notes ? `Note to driver: ${selected.notes}` : ''
    const combinedNotes = [notes.trim(), driverNote].filter(Boolean).join(' — ') || undefined
    place.mutate(
      {
        items: items.map((i) => ({ product_id: i.product.id, quantity: i.quantity })),
        deliveryAddress: selected.formatted,
        notes: combinedNotes,
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
          {/* Delivery address picker */}
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

          <Card className="space-y-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-fg">Order notes (optional)</label>
              <input value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Anything else we should know?" className={inputCls} />
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted">Total</span>
              <span className="font-display text-2xl text-fg">{peso(total())}</span>
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
