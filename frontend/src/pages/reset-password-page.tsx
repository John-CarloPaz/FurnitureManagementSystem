import { useState, type FormEvent } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { Logo } from '@/components/brand/logo'
import { resetPassword } from '@/lib/auth-api'
import { apiError } from '@/lib/api-error'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

export function ResetPasswordPage() {
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const token = params.get('token') ?? ''
  const email = params.get('email') ?? ''

  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [pending, setPending] = useState(false)

  const invalidLink = !token || !email

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setError(null)
    setPending(true)
    try {
      await resetPassword({ token, email, password, password_confirmation: confirm })
      navigate('/login?reset=1', { replace: true })
    } catch (err) {
      setError(apiError(err, 'Could not reset your password. The link may have expired.'))
    } finally {
      setPending(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-bg p-4">
      <form onSubmit={onSubmit} className="w-full max-w-sm space-y-6">
        <div className="flex flex-col items-center gap-3 text-center">
          <span className="flex h-14 w-14 items-center justify-center rounded-[var(--radius-md)] bg-walnut text-walnut-foreground">
            <Logo className="h-8 w-8" />
          </span>
          <div>
            <h1 className="font-display text-2xl text-fg">Choose a new password</h1>
            {email && <p className="text-sm text-muted">for {email}</p>}
          </div>
        </div>

        <div className="space-y-4 rounded-[var(--radius-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-md)]">
          {invalidLink ? (
            <p className="text-sm text-[var(--status-danger)]">This reset link is invalid or incomplete. Please request a new one.</p>
          ) : (
            <>
              {error && (
                <div className="rounded-[var(--radius-sm)] px-3 py-2 text-sm" style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}>
                  {error}
                </div>
              )}
              <div className="space-y-1.5">
                <label className="text-sm font-medium text-fg" htmlFor="password">New password</label>
                <input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" className={inputCls} required />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium text-fg" htmlFor="confirm">Confirm password</label>
                <input id="confirm" type="password" value={confirm} onChange={(e) => setConfirm(e.target.value)} placeholder="••••••••" className={inputCls} required />
              </div>
              <Button type="submit" className="w-full" size="pill" disabled={pending}>
                {pending ? 'Resetting…' : 'Reset password'}
              </Button>
            </>
          )}
        </div>

        <p className="text-center text-sm text-muted">
          <Link to="/forgot-password" className="text-walnut hover:underline">Request a new link</Link>
          {' · '}
          <Link to="/login" className="text-walnut hover:underline">Sign in</Link>
        </p>
      </form>
    </div>
  )
}
