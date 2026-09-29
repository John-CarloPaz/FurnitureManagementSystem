import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ArrowLeft, ShoppingBag, Check, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { ModelViewer } from '@/components/orders/model-viewer'
import { StarRating } from '@/components/storefront/star-rating'
import { useShopProduct } from '@/hooks/use-storefront'
import { useReviews, useSubmitReview, useDeleteReview } from '@/hooks/use-reviews'
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
  const submitReview = useSubmitReview(productId)
  const removeReview = useDeleteReview(productId)
  const add = useCart((s) => s.add)
  const [added, setAdded] = useState(false)

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

  return (
    <div className="space-y-6">
      <Link to="/shop" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
        <ArrowLeft size={15} /> Back to shop
      </Link>

      <div className="grid gap-8 lg:grid-cols-2">
        <div>
          {product.model_url ? (
            <ModelViewer url={fileUrl(product.model_url)} format={product.model_format ?? 'glb'} />
          ) : product.image_url ? (
            <img src={product.image_url} alt={product.name} className="aspect-square w-full rounded-[var(--radius-md)] object-cover" />
          ) : (
            <div className="flex aspect-square items-center justify-center rounded-[var(--radius-md)] bg-surface-2 text-muted">No preview</div>
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

        {isAuthenticated && (
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
