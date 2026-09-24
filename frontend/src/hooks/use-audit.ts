import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { fetchAuditLogs, type AuditEvent } from '@/lib/audit-api'

export function useAuditLogs(page: number, filters: { entity?: string; event?: AuditEvent }) {
  return useQuery({
    queryKey: ['audit-logs', page, filters],
    queryFn: () => fetchAuditLogs({ page, ...filters }),
    placeholderData: keepPreviousData,
  })
}
