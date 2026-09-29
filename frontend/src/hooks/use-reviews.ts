import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { deleteReview, fetchReviews, submitReview } from '@/lib/reviews-api'

export function useReviews(productId: number) {
  return useQuery({ queryKey: ['reviews', productId], queryFn: () => fetchReviews(productId), enabled: !!productId })
}

export function useSubmitReview(productId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (vars: { rating: number; comment?: string }) => submitReview(productId, vars.rating, vars.comment),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['reviews', productId] })
      qc.invalidateQueries({ queryKey: ['shop-product', productId] })
    },
  })
}

export function useDeleteReview(productId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteReview(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['reviews', productId] }),
  })
}
