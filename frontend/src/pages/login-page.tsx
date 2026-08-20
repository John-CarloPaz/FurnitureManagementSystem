import { useState, type FormEvent } from 'react'
import { Button } from '@/components/ui/button'
import { Logo } from '@/components/brand/logo'
import { useLogin } from '@/hooks/use-auth'
import { useNavigate } from 'react-router-dom'
import { AxiosError } from 'axios'

export function LoginPage() {
  const navigate = useNavigate()
  const loginMut = useLogin()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')

  const onSubmit = (e: FormEvent) => {
    e.preventDefault()
    loginMut.mutate(
      { email, password },
      { onSuccess: () => navigate('/', { replace: true }) },
    )
  }

  const error = loginMut.isError
    ? ((loginMut.error as AxiosError<{ message?: string }>).response?.data?.message ??
      'Login failed. Check your credentials.')
    : null

  return (
    <div className="flex min-h-screen items-center justify-center bg-bg p-4">
      <form onSubmit={onSubmit} className="w-full max-w-sm space-y-6">
        <div className="flex flex-col items-center gap-3 text-center">
          <span className="flex h-14 w-14 items-center justify-center rounded-[var(--radius-md)] bg-walnut text-walnut-foreground">
            <Logo className="h-8 w-8" />
          </span>
          <div>
            <h1 className="font-display text-2xl text-fg">Cedarside Holding Corp.</h1>
            <p className="text-sm text-muted">Sign in to your workspace</p>
          </div>
        </div>

        <div className="space-y-4 rounded-[var(--radius-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-md)]">
          {error && (
            <div
              className="rounded-[var(--radius-sm)] px-3 py-2 text-sm"
              style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}
            >
              {error}
            </div>
          )}
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="email">Email</label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="password">Password</label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              className="w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
            />
          </div>
          <Button type="submit" className="w-full" size="pill" disabled={loginMut.isPending}>
            {loginMut.isPending ? 'Signing in…' : 'Sign in'}
          </Button>
        </div>
      </form>
    </div>
  )
}
