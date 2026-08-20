import { useQuery } from '@tanstack/react-query'
import { fetchBottleneckAdvice, fetchSchedule } from '@/lib/dss-api'

export function useSchedule(enabled: boolean) {
  return useQuery({ queryKey: ['dss', 'schedule'], queryFn: fetchSchedule, enabled })
}

export function useBottleneckAdvice(enabled: boolean) {
  return useQuery({ queryKey: ['dss', 'bottlenecks'], queryFn: fetchBottleneckAdvice, enabled })
}
