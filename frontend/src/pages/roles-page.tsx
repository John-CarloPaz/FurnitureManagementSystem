import { useState } from 'react'
import { Plus, ShieldCheck, Lock, Pencil, Trash2, Users as UsersIcon } from 'lucide-react'
import { Card, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { RoleForm } from '@/components/access/role-form'
import { useAuth } from '@/hooks/use-auth'
import { useCreateRole, useDeleteRole, usePermissionCatalog, useRoles, useUpdateRole } from '@/hooks/use-roles'
import { apiError } from '@/lib/api-error'
import { prettyRole, type Role } from '@/lib/roles-api'

type Editing = { mode: 'new' } | { mode: 'edit'; role: Role } | null

export function RolesPage() {
  const { has } = useAuth()
  const canManage = has('roles.create')
  const roles = useRoles()
  const catalog = usePermissionCatalog()
  const create = useCreateRole()
  const update = useUpdateRole()
  const remove = useDeleteRole()

  const [editing, setEditing] = useState<Editing>(null)
  const [error, setError] = useState<string | null>(null)

  const close = () => {
    setEditing(null)
    setError(null)
  }

  const submit = (payload: { name: string; permissions: string[] }) => {
    setError(null)
    if (editing?.mode === 'edit') {
      update.mutate(
        { id: editing.role.id, name: payload.name, permissions: payload.permissions },
        { onSuccess: close, onError: (e) => setError(apiError(e)) },
      )
    } else {
      create.mutate(payload, { onSuccess: close, onError: (e) => setError(apiError(e)) })
    }
  }

  const onDelete = (role: Role) => {
    if (!window.confirm(`Delete the "${prettyRole(role.name)}" role?`)) return
    remove.mutate(role.id, { onError: (e) => window.alert(apiError(e)) })
  }

  const catalogGroups = catalog.data ?? []

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display text-3xl text-fg">Roles &amp; permissions</h1>
          <p className="mt-1 text-muted">
            {canManage ? 'Create roles and tick exactly what each one can access.' : 'The roles that govern access in this workspace.'}
          </p>
        </div>
        {canManage && !editing && (
          <Button size="pill" onClick={() => setEditing({ mode: 'new' })}>
            <Plus size={16} /> New role
          </Button>
        )}
      </div>

      {editing && (
        <Card className="space-y-4">
          <CardTitle>{editing.mode === 'edit' ? `Edit ${prettyRole(editing.role.name)}` : 'New role'}</CardTitle>
          <RoleForm
            catalog={catalogGroups}
            initial={editing.mode === 'edit' ? { name: prettyRole(editing.role.name), permissions: editing.role.permissions } : undefined}
            submitting={create.isPending || update.isPending}
            error={error}
            onSubmit={submit}
            onCancel={close}
          />
        </Card>
      )}

      {roles.isLoading ? (
        <p className="p-8 text-center text-muted">Loading roles…</p>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2">
          {roles.data?.map((role) => (
            <Card key={role.id} className="space-y-3">
              <div className="flex items-start justify-between gap-2">
                <div className="flex items-center gap-2">
                  <span className="flex h-9 w-9 items-center justify-center rounded-[var(--radius-sm)] bg-amber-soft text-walnut">
                    <ShieldCheck size={18} />
                  </span>
                  <div>
                    <p className="font-medium text-fg">{prettyRole(role.name)}</p>
                    <p className="flex items-center gap-1 text-xs text-muted">
                      <UsersIcon size={12} /> {role.users_count} {role.users_count === 1 ? 'user' : 'users'}
                    </p>
                  </div>
                </div>
                {role.is_system && (
                  <span className="inline-flex items-center gap-1 rounded-full bg-surface-2 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-muted">
                    <Lock size={10} /> System
                  </span>
                )}
              </div>

              <p className="text-xs text-muted">
                {role.permissions.length} permission{role.permissions.length === 1 ? '' : 's'} granted
              </p>

              {canManage && !role.is_system && (
                <div className="flex gap-2">
                  <Button size="sm" variant="secondary" onClick={() => { setError(null); setEditing({ mode: 'edit', role }) }}>
                    <Pencil size={14} /> Edit
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => onDelete(role)} disabled={remove.isPending}>
                    <Trash2 size={14} /> Delete
                  </Button>
                </div>
              )}
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
