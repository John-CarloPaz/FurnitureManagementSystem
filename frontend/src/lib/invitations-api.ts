import { AxiosError } from 'axios'
import { api } from './api'
import type { User } from './auth-api'

export interface Invitation {
  id: number
  email: string
  role: string
  status: 'pending' | 'accepted' | 'expired'
  accept_url: string
  invited_by: string | null
  expires_at: string
  accepted_at: string | null
  created_at: string
}

export interface CreatedInvitation {
  invitation: Invitation
  email_sent: boolean
}

export async function fetchInvitations(): Promise<Invitation[]> {
  const { data } = await api.get('/invitations')
  return data.data
}

export async function createInvitation(payload: { email: string; role: string }): Promise<CreatedInvitation> {
  const { data } = await api.post('/invitations', payload)
  return { invitation: data.data, email_sent: data.meta?.email_sent ?? false }
}

export async function revokeInvitation(id: number): Promise<void> {
  await api.delete(`/invitations/${id}`)
}

// ---- Public accept flow ----

export interface InvitationPreview {
  valid: boolean
  email?: string
  role?: string
  reason?: string
}

/** Reads a token; a 404 (invalid/expired) still carries a structured {valid:false}. */
export async function readInvitation(token: string): Promise<InvitationPreview> {
  try {
    const { data } = await api.get(`/invitations/accept/${token}`)
    return data.data
  } catch (err) {
    const body = (err as AxiosError<{ data?: InvitationPreview }>).response?.data
    return body?.data ?? { valid: false, reason: 'error' }
  }
}

export async function acceptInvitation(
  token: string,
  payload: { name: string; password: string; password_confirmation: string },
): Promise<{ token: string; user: User }> {
  const { data } = await api.post(`/invitations/accept/${token}`, payload)
  return data.data
}
