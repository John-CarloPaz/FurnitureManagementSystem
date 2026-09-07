import { useState } from 'react'
import { UserPlus, Trash2, Copy, Check } from 'lucide-react'
import { Card, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { InviteUserForm } from '@/components/access/invite-user-form'
import { useAuth } from '@/hooks/use-auth'
import { useRoles } from '@/hooks/use-roles'
import { useInvitations, useRevokeInvitation } from '@/hooks/use-invitations'
import { useUsers, useUpdateUser, useDeleteUser } from '@/hooks/use-users'
import { apiError } from '@/lib/api-error'
import { prettyRole } from '@/lib/roles-api'
import type { Invitation } from '@/lib/invitations-api'

const INVITE_COLOR: Record<Invitation['status'], string> = {
  pending: 'var(--status-warning)',
  accepted: 'var(--status-success)',
  expired: 'var(--status-neutral)',
}

function CopyButton({ url }: { url: string }) {
  const [copied, setCopied] = useState(false)
  return (
    <button
      onClick={async () => {
        try {
          await navigator.clipboard.writeText(url)
          setCopied(true)
          setTimeout(() => setCopied(false), 1500)
        } catch { /* ignore */ }
      }}
      className="inline-flex items-center gap-1 text-xs text-walnut hover:underline"
    >
      {copied ? <Check size={12} /> : <Copy size={12} />} {copied ? 'Copied' : 'Copy link'}
    </button>
  )
}

function PendingInvitations() {
  const invitations = useInvitations()
  const revoke = useRevokeInvitation()

  if (invitations.isLoading) return null
  const rows = invitations.data ?? []
  if (!rows.length) return null

  return (
    <Card className="p-0">
      <div className="border-b border-border px-6 py-4"><CardTitle>Invitations</CardTitle></div>
      <table className="w-full text-sm">
        <tbody>
          {rows.map((inv) => (
            <tr key={inv.id} className="border-b border-border last:border-0">
              <td className="px-6 py-3">
                <p className="text-fg">{inv.email}</p>
                <p className="text-xs text-muted">{prettyRole(inv.role)}{inv.invited_by ? ` · invited by ${inv.invited_by}` : ''}</p>
              </td>
              <td className="px-6 py-3">
                <span className="text-xs font-medium" style={{ color: INVITE_COLOR[inv.status] }}>
                  {inv.status.toUpperCase()}
                </span>
              </td>
              <td className="px-6 py-3">{inv.status === 'pending' && <CopyButton url={inv.accept_url} />}</td>
              <td className="px-6 py-3 text-right">
                {inv.status !== 'accepted' && (
                  <button
                    onClick={() => revoke.mutate(inv.id)}
                    className="inline-flex items-center gap-1 text-xs text-muted hover:text-[var(--status-danger)]"
                  >
                    <Trash2 size={13} /> Revoke
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </Card>
  )
}

export function UsersPage() {
  const { user, has } = useAuth()
  const canInvite = has('invitations.create')
  const canUpdate = has('users.update')
  const canDelete = has('users.delete')

  const users = useUsers()
  const roles = useRoles()
  const updateUser = useUpdateUser()
  const deleteUser = useDeleteUser()
  const [inviting, setInviting] = useState(false)

  // super_admin (roles.create) can assign any role; others can't hand out the privileged ones.
  const assignableRoles = (roles.data ?? []).filter(
    (r) => has('roles.create') || (r.name !== 'super_admin' && r.name !== 'admin'),
  )

  const onDelete = (id: number, name: string) => {
    if (!window.confirm(`Remove ${name}?`)) return
    deleteUser.mutate(id, { onError: (e) => window.alert(apiError(e)) })
  }

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display text-3xl text-fg">Users</h1>
          <p className="mt-1 text-muted">Invite teammates and manage who can access what.</p>
        </div>
        {canInvite && !inviting && (
          <Button size="pill" onClick={() => setInviting(true)}>
            <UserPlus size={16} /> Invite user
          </Button>
        )}
      </div>

      {canInvite && inviting && (
        <Card className="space-y-4">
          <CardTitle>Invite a user</CardTitle>
          <p className="-mt-2 text-sm text-muted">
            They'll get an email link to set their own name and password, then join with the role you pick.
          </p>
          <InviteUserForm roles={assignableRoles} onDone={() => setInviting(false)} />
        </Card>
      )}

      {has('invitations.viewAny') && <PendingInvitations />}

      <Card className="p-0">
        <div className="border-b border-border px-6 py-4"><CardTitle>Team members</CardTitle></div>
        {users.isLoading ? (
          <p className="p-8 text-center text-muted">Loading users…</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs uppercase tracking-wide text-muted">
                <th className="px-6 py-3 font-medium">Name</th>
                <th className="px-6 py-3 font-medium">Role</th>
                <th className="px-6 py-3 font-medium">Status</th>
                <th className="px-6 py-3" />
              </tr>
            </thead>
            <tbody>
              {users.data?.map((u) => {
                const isSelf = u.id === user?.id
                return (
                  <tr key={u.id} className="border-t border-border">
                    <td className="px-6 py-3">
                      <p className="text-fg">{u.name}{isSelf && <span className="ml-1 text-xs text-muted">(you)</span>}</p>
                      <p className="text-xs text-muted">{u.email}</p>
                    </td>
                    <td className="px-6 py-3">
                      {canUpdate && !isSelf ? (
                        <select
                          value={u.roles[0] ?? ''}
                          onChange={(e) => updateUser.mutate({ id: u.id, role: e.target.value }, { onError: (err) => window.alert(apiError(err)) })}
                          className="rounded-[var(--radius-sm)] border border-border bg-bg px-2 py-1 text-sm text-fg"
                        >
                          {(roles.data ?? []).map((r) => (
                            <option key={r.id} value={r.name}>{prettyRole(r.name)}</option>
                          ))}
                        </select>
                      ) : (
                        <span className="text-muted">{u.roles.map(prettyRole).join(', ') || '—'}</span>
                      )}
                    </td>
                    <td className="px-6 py-3">
                      {canUpdate && !isSelf ? (
                        <button
                          onClick={() => updateUser.mutate({ id: u.id, is_active: !u.is_active }, { onError: (err) => window.alert(apiError(err)) })}
                          className="text-xs font-medium"
                          style={{ color: u.is_active ? 'var(--status-success)' : 'var(--status-neutral)' }}
                        >
                          {u.is_active ? 'Active' : 'Inactive'}
                        </button>
                      ) : (
                        <span className="text-xs font-medium" style={{ color: u.is_active ? 'var(--status-success)' : 'var(--status-neutral)' }}>
                          {u.is_active ? 'Active' : 'Inactive'}
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-3 text-right">
                      {canDelete && !isSelf && (
                        <button
                          onClick={() => onDelete(u.id, u.name)}
                          className="inline-flex items-center gap-1 text-xs text-muted hover:text-[var(--status-danger)]"
                        >
                          <Trash2 size={13} /> Remove
                        </button>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        )}
      </Card>
    </div>
  )
}
