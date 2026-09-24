import { useNavigate } from 'react-router-dom'
import { Box, Boxes } from 'lucide-react'
import { peso } from '@/lib/status'
import type { ShopProduct } from '@/lib/storefront-api'

export function ShopProductCard({ product }: { product: ShopProduct }) {
  const navigate = useNavigate()
  return (
    <button
      onClick={() => navigate(`/shop/product/${product.id}`)}
      className="group flex flex-col overflow-hidden rounded-[var(--radius-lg)] border border-border bg-surface text-left shadow-[var(--shadow-sm)] transition-all hover:-translate-y-0.5 hover:shadow-[var(--shadow-md)]"
    >
      <div className="relative aspect-square w-full overflow-hidden bg-surface-2">
        {product.image_url ? (
          <img src={product.image_url} alt={product.name} className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
        ) : (
          <div className="flex h-full items-center justify-center text-muted"><Box size={40} /></div>
        )}
        {product.has_model && (
          <span className="absolute left-2 top-2 flex items-center gap-1 rounded-full bg-black/50 px-2 py-0.5 text-[10px] font-medium text-white backdrop-blur">
            <Boxes size={11} /> 3D
          </span>
        )}
      </div>
      <div className="space-y-1 p-4">
        {product.category && <p className="text-[11px] uppercase tracking-wide text-muted">{product.category}</p>}
        <p className="font-medium text-fg">{product.name}</p>
        <p className="font-display text-lg text-walnut">{peso(product.base_price)}</p>
      </div>
    </button>
  )
}
