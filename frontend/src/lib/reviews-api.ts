import { api } from './api'

export interface Review {
  id: number
  product_id: number
  rating: number
  comment: string | null
  reviewer: string | null
  is_mine: boolean
  created_at: string
}

export async function fetchReviews(productId: number): Promise<Review[]> {
  return (await api.get(`/shop/products/${productId}/reviews`)).data.data
}

export async function submitReview(productId: number, rating: number, comment?: string): Promise<Review> {
  return (await api.post(`/shop/products/${productId}/reviews`, { rating, comment })).data.data
}

export async function deleteReview(id: number): Promise<void> {
  await api.delete(`/reviews/${id}`)
}

export async function fetchCanReview(productId: number): Promise<boolean> {
  return (await api.get(`/shop/products/${productId}/can-review`)).data.data.can_review
}
