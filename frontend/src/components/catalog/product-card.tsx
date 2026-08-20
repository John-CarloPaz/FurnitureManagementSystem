import { Link } from 'react-router-dom'
import { Box, Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { PRODUCT_STATUS_META, peso } from '@/lib/status'
import { useCart } from '@/stores/cart'
import { useAuth } from '@/hooks/use-auth'
import type { Product } from '@/lib/products-api'

export function ProductCard({ product }: { product: Product }) {
  const add = useCart((s) => s.add)
  const { has } = useAuth()
  const meta = PRODUCT_STATUS_META[product.status]
  const canOrder = has('orders.place') && product.status === 'PUBLISHED'

  return (
    <div className="overflow-hidden rounded-[var(--radius-md)] border border-border bg-surface shadow-[var(--shadow-sm)] transition-shadow hover:shadow-[var(--shadow-md)]">
      <Link to={`/catalog/${product.id}`} className="block">
        <div className="flex h-40 items-center justify-center bg-surface-2 text-muted">
          <Box size={40} />
        </div>
      </Link>
      <div className="space-y-2 p-4">
        <div className="flex items-start justify-between gap-2">
          <Link to={`/catalog/${product.id}`} className="font-display text-lg text-fg hover:text-walnut">
            {product.name}
          </Link>
          {has('products.viewAny') && (
            <span
              className="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
              style={{ backgroundColor: `color-mix(in srgb, ${meta.color} 14%, transparent)`, color: meta.color }}
            >
              {meta.label}
            </span>
          )}
        </div>
        {product.category && <p className="text-xs text-muted">{product.category}</p>}
        <p className="font-display text-xl text-fg">{peso(product.base_price)}</p>
        {canOrder && (
          <Button size="sm" className="w-full" onClick={() => add(product)}>
            <Plus size={15} /> Add to order
          </Button>
        )}
      </div>
    </div>
  )
}
