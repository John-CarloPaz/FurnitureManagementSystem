import { api } from './api'

export interface Voucher {
  id: number
  code: string
  type: 'percent' | 'fixed'
  value: string
  min_spend: string | null
  max_discount: string | null
  starts_at: string | null
  expires_at: string | null
  usage_limit: number | null
  used_count: number
  is_active: boolean
  description: string | null
}

export interface VoucherInput {
  code: string
  type: 'percent' | 'fixed'
  value: number
  min_spend?: number | null
  max_discount?: number | null
  starts_at?: string | null
  expires_at?: string | null
  usage_limit?: number | null
  is_active?: boolean
  description?: string | null
}

export interface VoucherPreview {
  valid: boolean
  discount: number
  code: string | null
  message: string | null
}

export async function fetchVouchers(): Promise<Voucher[]> {
  return (await api.get('/vouchers')).data.data
}

export async function createVoucher(payload: VoucherInput): Promise<Voucher> {
  return (await api.post('/vouchers', payload)).data.data
}

export async function updateVoucher(id: number, payload: VoucherInput): Promise<Voucher> {
  return (await api.patch(`/vouchers/${id}`, payload)).data.data
}

export async function deleteVoucher(id: number): Promise<void> {
  await api.delete(`/vouchers/${id}`)
}

export async function previewVoucher(code: string, subtotal: number): Promise<VoucherPreview> {
  return (await api.post('/vouchers/preview', { code, subtotal })).data.data
}
