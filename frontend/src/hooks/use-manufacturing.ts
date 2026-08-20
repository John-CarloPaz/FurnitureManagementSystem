import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  completeStage,
  fetchProduction,
  fetchShopFloor,
  startStage,
  type StageName,
} from '@/lib/manufacturing-api'

/** Live shop-floor board — polls every 10s (Pusher push added when keys are set). */
export function useShopFloor() {
  return useQuery({ queryKey: ['shop-floor'], queryFn: fetchShopFloor, refetchInterval: 10_000 })
}

export function useProduction(orderId: number) {
  return useQuery({
    queryKey: ['production', orderId],
    queryFn: () => fetchProduction(orderId),
    enabled: !!orderId,
    refetchInterval: 10_000,
  })
}

/** Stage mutations that refresh the shop-floor + production views + the order. */
export function useStageActions(orderId?: number) {
  const qc = useQueryClient()
  const invalidate = () => {
    qc.invalidateQueries({ queryKey: ['shop-floor'] })
    if (orderId) {
      qc.invalidateQueries({ queryKey: ['production', orderId] })
      qc.invalidateQueries({ queryKey: ['orders', orderId] })
    }
  }

  const start = useMutation({
    mutationFn: (vars: { itemId: number; stage: StageName }) => startStage(vars.itemId, vars.stage),
    onSuccess: invalidate,
  })
  const complete = useMutation({
    mutationFn: (vars: { itemId: number; stage: StageName; qcPassed?: boolean }) =>
      completeStage(vars.itemId, vars.stage, vars.qcPassed),
    onSuccess: invalidate,
  })

  return { start, complete }
}
