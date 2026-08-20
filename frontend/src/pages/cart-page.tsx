import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { Trash2, ArrowLeft } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useCart } from '@/stores/cart'
import { usePlaceOrder } from '@/hooks/use-orders'
import { peso } from '@/lib/status'

export function CartPage() {
  const navigate = useNavigate()
  const { items, setQty, remove, clear, total } = useCart()
  const place = usePlaceOrder()
  const [address, setAddress] = useState('')
  const [notes, setNotes] = useState('')

  const onPlace = () => {
    place.mutate(
      {
        items: items.map((i) => ({ product_id: i.product.id, quantity: i.quantity })),
        deliveryAddress: address || undefined,
        notes: notes || undefined,
      },
      {
        onSuccess: (o) => {
          clear()
          navigate(`/orders/${o.id}`)
        },
      },
    )
  }

  if (!items.length) {
    return (
      <div className="mx-auto max-w-3xl space-y-4">
        <h1 className="font-display text-3xl text-fg">Your order</h1>
        <Card className="text-center text-muted">
          Nothing here yet. <Link to="/catalog" className="text-walnut">Browse the catalog</Link>.
        </Card>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <Link to="/catalog" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
        <ArrowLeft size={15} /> Continue browsing
      </Link>
      <h1 className="font-display text-3xl text-fg">Your order</h1>

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
                  <input
                    type="number"
                    min="1"
                    value={quantity}
                    onChange={(e) => setQty(product.id, Number(e.target.value))}
                    className="w-16 rounded-[var(--radius-sm)] border border-border bg-bg px-2 py-1 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
                  />
                </td>
                <td className="px-5 py-3 text-right font-mono text-fg">
                  {peso(parseFloat(product.base_price) * quantity)}
                </td>
                <td className="px-5 py-3 text-right">
                  <button onClick={() => remove(product.id)} className="text-muted hover:text-[var(--status-danger)]">
                    <Trash2 size={16} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>

      <Card className="space-y-4">
        <div className="flex items-center justify-between">
          <span className="text-muted">Total</span>
          <span className="font-display text-2xl text-fg">{peso(total())}</span>
        </div>
        <div className="space-y-1.5">
          <label className="text-sm font-medium text-fg">Delivery address</label>
          <textarea
            rows={2}
            value={address}
            onChange={(e) => setAddress(e.target.value)}
            className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
          />
        </div>
        <div className="space-y-1.5">
          <label className="text-sm font-medium text-fg">Notes (optional)</label>
          <input
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
          />
        </div>
        <Button className="w-full" size="pill" onClick={onPlace} disabled={place.isPending}>
          {place.isPending ? 'Placing order…' : 'Place order'}
        </Button>
        {place.isError && <p className="text-sm text-[var(--status-danger)]">Couldn't place the order. Please try again.</p>}
      </Card>
    </div>
  )
}
