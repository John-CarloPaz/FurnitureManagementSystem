import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ArrowLeft, ShoppingBag, Check, Trash2, Boxes, Image as ImageIcon } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { ModelViewer } from '@/components/orders/model-viewer'
import { StarRating } from '@/components/storefront/star-rating'
import { DimensionRuler } from '@/components/storefront/dimension-ruler'
import { useShopProduct } from '@/hooks/use-storefront'
import { useReviews, useSubmitReview, useDeleteReview, useCanReview } from '@/hooks/use-reviews'
import { useAuth } from '@/hooks/use-auth'
import { useCart } from '@/stores/cart'
import { fileUrl } from '@/lib/api'
import { apiError } from '@/lib/api-error'
import { peso } from '@/lib/status'

export function StorefrontProduct() {
  const { id } = useParams()
  const productId = Number(id)
  const { data: product, isLoading } = useShopProduct(productId)
  const { isAuthenticated } = useAuth()
  const { data: reviews = [] } = useReviews(productId)
  const { data: canReview } = useCanReview(productId, isAuthenticated)
  const submitReview = useSubmitReview(productId)
  const removeReview = useDeleteReview(productId)
  const add = useCart((s) => s.add)
  const [added, setAdded] = useState(false)
  const [activeVariationId, setActiveVariationId] = useState<number | null>(null)
  const [view, setView] = useState<'3d' | 'photo'>('3d')

  const myReview = reviews.find((r) => r.is_mine)
  const [rating, setRating] = useState(0)
  const [comment, setComment] = useState('')
  const [reviewError, setReviewError] = useState<string | null>(null)

  const onReview = () => {
    setReviewError(null)
    if (rating < 1) { setReviewError('Please pick a star rating.'); return }
    submitReview.mutate(
      { rating, comment: comment.trim() || undefined },
      { onSuccess: () => { setRating(0); setComment('') }, onError: (e) => setReviewError(apiError(e, 'You can review this once your order is delivered.')) },
    )
  }

  if (isLoading) return <p className="py-16 text-center text-muted">Loading…</p>
  if (!product) {
    return <p className="py-16 text-center text-muted">Product not found. <Link to="/shop" className="text-walnut">Back to shop</Link></p>
  }

  const onAdd = () => {
    add({ id: product.id, name: product.name, base_price: product.base_price })
    setAdded(true)
    setTimeout(() => setAdded(false), 1500)
  }

  const specs: [string, string | null][] = [
    ['Category', product.category],
    ['Material', product.material],
    ['Wood type', product.wood_type],
    ['Finish', product.finish],
    ['Dimensions', product.dimensions_label],
    ['Lead time', product.lead_time_days ? `${product.lead_time_days} days` : null],
  ]

  // The selected variation drives the display: its own 3D model + photo when present,
  // otherwise fall back to the base model (tinted with the variation's color) and base photo.
  const activeVar = product.variations?.find((v) => v.id === activeVariationId) ?? null
  const modelUrl = activeVar?.model_url ?? product.model_url ?? null
  const modelFormat = activeVar?.model_format ?? product.model_format ?? 'glb'
  const tint = activeVar && !activeVar.model_url ? activeVar.color_hex : undefined
  const photoUrl = activeVar?.image_url ?? product.image_url ?? null

  return (
    <div className="space-y-6">
      <Link to="/shop" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
        <ArrowLeft size={15} /> Back to shop
      </Link>

      <div className="grid gap-8 lg:grid-cols-2">
        <div className="space-y-4">
          {/* 3D / Photo switch — only when both exist */}
          {modelUrl && photoUrl && (
            <div className="inline-flex rounded-full border border-border bg-surface p-1 text-sm">
              <button
                onClick={() => setView('3d')}
                className={`inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 transition-colors ${view === '3d' ? 'bg-walnut text-walnut-foreground' : 'text-muted hover:text-fg'}`}
              >
                <Boxes size={14} /> 3D
              </button>
              <button
                onClick={() => setView('photo')}
                className={`inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 transition-colors ${view === 'photo' ? 'bg-walnut text-walnut-foreground' : 'text-muted hover:text-fg'}`}
              >
                <ImageIcon size={14} /> Photo
              </button>
            </div>
          )}

          {modelUrl && view === '3d' ? (
            <ModelViewer key={modelUrl} url={fileUrl(modelUrl)} format={modelFormat} color={tint} />
          ) : photoUrl ? (
            <div className="relative overflow-hidden rounded-[var(--radius-lg)] border border-black/5">
              <img src={photoUrl} alt={product.name} className="aspect-square w-full bg-white object-contain" />
              <DimensionRuler width={product.width_cm} height={product.height_cm} depth={product.depth_cm} />
            </div>
          ) : (
            <div className="flex h-[460px] items-center justify-center rounded-[var(--radius-lg)] bg-surface-2 text-muted">No preview</div>
          )}

          {/* Color variations — each swaps the 3D model + photo (or tints the base) */}
          {product.variations && product.variations.length > 0 && (
            <div className="space-y-2">
              <p className="text-xs font-medium uppercase tracking-wide text-muted">
                Finish{activeVar ? <span className="ml-2 normal-case text-fg">{activeVar.name}</span> : ''}
              </p>
              <div className="flex flex-wrap items-center gap-2.5">
                <button
                  onClick={() => setActiveVariationId(null)}
                  className={`h-9 rounded-full border px-3.5 text-xs font-medium transition-colors ${activeVariationId === null ? 'border-walnut bg-[var(--amber-soft)] text-walnut' : 'border-border text-muted hover:text-fg'}`}
                >
                  Original
                </button>
                {product.variations.map((v) => (
                  <button
                    key={v.id}
                    onClick={() => { setActiveVariationId(v.id); if (v.model_url) setView('3d') }}
                    aria-label={v.name}
                    title={v.name}
                    className={`h-9 w-9 rounded-full border transition-transform hover:scale-110 ${activeVariationId === v.id ? 'border-walnut ring-2 ring-[var(--amber)] ring-offset-2 ring-offset-bg' : 'border-black/10'}`}
                    style={{ backgroundColor: v.color_hex }}
                  />
                ))}
              </div>
            </div>
          )}
        </div>

        <div className="space-y-5">
          {product.category && <p className="text-xs uppercase tracking-wide text-muted">{product.category}</p>}
          <h1 className="font-display text-3xl text-fg">{product.name}</h1>
          {product.rating_count > 0 && (
            <div className="flex items-center gap-2 text-sm text-muted">
              <StarRating value={product.rating_avg ?? 0} />
              <span>{product.rating_avg?.toFixed(1)} · {product.rating_count} review{product.rating_count > 1 ? 's' : ''}</span>
            </div>
          )}
          <p className="font-display text-3xl text-walnut">{peso(product.base_price)}</p>
          {product.description && <p className="text-muted">{product.description}</p>}

          <dl className="grid grid-cols-1 gap-y-2 border-t border-border pt-4 text-sm sm:grid-cols-2 sm:gap-x-8">
            {specs.filter(([, v]) => v).map(([k, v]) => (
              <div key={k} className="flex justify-between border-b border-border pb-2">
                <dt className="text-muted">{k}</dt>
                <dd className="text-fg">{v}</dd>
              </div>
            ))}
          </dl>

          <Button size="pill" className="w-full" onClick={onAdd}>
            {added ? <><Check size={16} /> Added to cart</> : <><ShoppingBag size={16} /> Add to cart</>}
          </Button>
        </div>
      </div>

      {/* Reviews */}
      <section className="space-y-4 border-t border-border pt-8">
        <h2 className="font-display text-2xl text-fg">Reviews</h2>

        {isAuthenticated && canReview && (
          <Card className="space-y-3">
            <p className="text-sm font-medium text-fg">{myReview ? 'Update your review' : 'Write a review'}</p>
            <StarRating value={rating || myReview?.rating || 0} size={22} onChange={setRating} />
            <textarea
              rows={3}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              placeholder={myReview?.comment ?? 'Share what you think about this piece…'}
              className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
            />
            {reviewError && <p className="text-sm text-[var(--status-danger)]">{reviewError}</p>}
            <Button size="pill" onClick={onReview} disabled={submitReview.isPending}>
              {submitReview.isPending ? 'Submitting…' : myReview ? 'Update review' : 'Submit review'}
            </Button>
          </Card>
        )}

        {reviews.length === 0 ? (
          <p className="text-muted">No reviews yet{isAuthenticated ? ' — be the first once your order arrives.' : '.'}</p>
        ) : (
          <div className="space-y-3">
            {reviews.map((r) => (
              <Card key={r.id} className="space-y-1.5">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <StarRating value={r.rating} size={14} />
                    <span className="text-sm font-medium text-fg">{r.reviewer ?? 'Customer'}{r.is_mine ? ' (you)' : ''}</span>
                  </div>
                  {r.is_mine && (
                    <button onClick={() => removeReview.mutate(r.id)} aria-label="Delete review" className="text-muted hover:text-[var(--status-danger)]"><Trash2 size={14} /></button>
                  )}
                </div>
                {r.comment && <p className="text-sm text-muted">{r.comment}</p>}
              </Card>
            ))}
          </div>
        )}
      </section>
    </div>
  )
}
