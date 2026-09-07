import { useState, type FormEvent, type ReactNode } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Button } from '@/components/ui/button'
import { Logo } from '@/components/brand/logo'
import { tokenStore } from '@/lib/api'
import { acceptInvitation, readInvitation } from '@/lib/invitations-api'
import { apiError } from '@/lib/api-error'
import { prettyRole } from '@/lib/roles-api'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

export function AcceptInvitationPage() {
  const { token = '' } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()

  const preview = useQuery({
    queryKey: ['invitation', token],
    queryFn: () => readInvitation(token),
    retry: false,
  })

  const [name, setName] = useState('')
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState<string | null>(null)

  const accept = useMutation({
    mutationFn: () =>
      acceptInvitation(token, { name, password, password_confirmation: confirm }),
    onSuccess: (res) => {
      tokenStore.set(res.token)
      qc.setQueryData(['me'], res.user)
      navigate('/', { replace: true })
    },
    onError: (e) => setError(apiError(e, 'Could not create your account.')),
  })

  const submit = (e: FormEvent) => {
    e.preventDefault()
    setError(null)
    accept.mutate()
  }

  const Shell = ({ children }: { children: ReactNode }) => (
    <div className="flex min-h-screen items-center justify-center bg-bg p-4">
      <div className="w-full max-w-sm space-y-6">
        <div className="flex flex-col items-center gap-3 text-center">
          <span className="flex h-14 w-14 items-center justify-center rounded-[var(--radius-md)] bg-walnut text-walnut-foreground">
            <Logo className="h-8 w-8" />
          </span>
          <h1 className="font-display text-2xl text-fg">Cedarside Holding Corp.</h1>
        </div>
        {children}
      </div>
    </div>
  )

  if (preview.isLoading) {
    return <Shell><p className="text-center text-muted">Checking your invitation…</p></Shell>
  }

  if (!preview.data?.valid) {
    return (
      <Shell>
        <div className="space-y-2 rounded-[var(--radius-lg)] border border-border bg-surface p-6 text-center shadow-[var(--shadow-md)]">
          <p className="font-medium text-fg">This invitation link isn't valid.</p>
          <p className="text-sm text-muted">It may have expired or already been used. Ask an admin to send a new one.</p>
          <Button variant="secondary" size="pill" className="mt-2" onClick={() => navigate('/login')}>Go to sign in</Button>
        </div>
      </Shell>
    )
  }

  return (
    <Shell>
      <form onSubmit={submit} className="space-y-4 rounded-[var(--radius-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-md)]">
        <div className="text-center">
          <p className="text-sm text-muted">You're joining as</p>
          <p className="font-display text-lg text-fg">{prettyRole(preview.data.role ?? '')}</p>
          <p className="text-xs text-muted">{preview.data.email}</p>
        </div>

        {error && (
          <div
            className="rounded-[var(--radius-sm)] px-3 py-2 text-sm"
            style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}
          >
            {error}
          </div>
        )}

        <div className="space-y-1.5">
          <label className="text-sm font-medium text-fg" htmlFor="name">Your name</label>
          <input id="name" value={name} onChange={(e) => setName(e.target.value)} className={inputCls} required />
        </div>
        <div className="space-y-1.5">
          <label className="text-sm font-medium text-fg" htmlFor="password">Password</label>
          <input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" className={inputCls} required />
        </div>
        <div className="space-y-1.5">
          <label className="text-sm font-medium text-fg" htmlFor="confirm">Confirm password</label>
          <input id="confirm" type="password" value={confirm} onChange={(e) => setConfirm(e.target.value)} placeholder="••••••••" className={inputCls} required />
        </div>

        <Button type="submit" className="w-full" size="pill" disabled={accept.isPending}>
          {accept.isPending ? 'Creating account…' : 'Create account & sign in'}
        </Button>
      </form>
    </Shell>
  )
}
