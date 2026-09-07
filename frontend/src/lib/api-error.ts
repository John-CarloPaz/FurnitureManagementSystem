import { AxiosError } from 'axios'

/** Best human-readable message from a Laravel error response (validation or plain). */
export function apiError(err: unknown, fallback = 'Something went wrong.'): string {
  const data = (err as AxiosError<{ message?: string; errors?: Record<string, string[]> }>).response?.data
  if (data?.errors) {
    const first = Object.values(data.errors)[0]?.[0]
    if (first) return first
  }
  return data?.message ?? fallback
}
