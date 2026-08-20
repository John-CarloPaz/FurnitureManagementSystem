import { api, tokenStore } from './api'

export interface User {
  id: number
  name: string
  email: string
  company: string | null
  is_active: boolean
  roles: string[]
  permissions: string[]
  created_at: string
}

export interface LoginResponse {
  token: string
  user: User
}

export async function login(email: string, password: string): Promise<LoginResponse> {
  const { data } = await api.post('/auth/login', { email, password })
  return data.data
}

export async function getMe(): Promise<User> {
  const { data } = await api.get('/auth/me')
  return data.data
}

export async function logout(): Promise<void> {
  try {
    await api.post('/auth/logout')
  } finally {
    tokenStore.clear()
  }
}
