import axios from 'axios'

const TOKEN_KEY = 'cedarside.token'

export const tokenStore = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (t: string) => localStorage.setItem(TOKEN_KEY, t),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

/**
 * Backend origin. In dev this is empty → the Vite proxy forwards `/api` to :8000.
 * In prod set `VITE_API_URL` (e.g. https://your-app.laravel.cloud) so the SPA on
 * Vercel calls the Laravel Cloud backend directly.
 */
export const API_ORIGIN = (import.meta.env.VITE_API_URL ?? '').replace(/\/$/, '')

/** Absolute URL for a server-relative path (e.g. a signed 3D/proof file URL). */
export const fileUrl = (path: string) => (/^https?:\/\//.test(path) ? path : `${API_ORIGIN}${path}`)

export const api = axios.create({
  baseURL: API_ORIGIN ? `${API_ORIGIN}/api/v1` : '/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = tokenStore.get()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      tokenStore.clear()
      if (window.location.pathname !== '/login') window.location.href = '/login'
    }
    return Promise.reject(err)
  },
)
