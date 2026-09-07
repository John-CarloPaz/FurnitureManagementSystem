import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import { PermissionMatrix } from './permission-matrix'
import type { PermissionGroup } from '@/lib/roles-api'

const catalog: PermissionGroup[] = [
  {
    key: 'products',
    label: 'Products & Catalogue',
    abilities: [
      { name: 'products.create', label: 'Create', type: 'crud', action: 'create' },
      { name: 'products.viewAny', label: 'Read', type: 'crud', action: 'read' },
      { name: 'products.publish', label: 'Publish', type: 'action' },
    ],
  },
]

describe('PermissionMatrix', () => {
  it('renders CRUD checkboxes and action chips, and toggles by permission name', () => {
    const onToggle = vi.fn()
    render(
      <PermissionMatrix groups={catalog} selected={new Set(['products.viewAny'])} onToggle={onToggle} />,
    )

    // Resource row + a checked Read cell + a Publish chip
    expect(screen.getAllByText('Products & Catalogue').length).toBeGreaterThan(0)
    expect(screen.getByLabelText('Products & Catalogue — Read')).toHaveAttribute('aria-pressed', 'true')
    expect(screen.getByLabelText('Products & Catalogue — Create')).toHaveAttribute('aria-pressed', 'false')

    fireEvent.click(screen.getByLabelText('Products & Catalogue — Create'))
    expect(onToggle).toHaveBeenCalledWith('products.create')

    fireEvent.click(screen.getByText('Publish'))
    expect(onToggle).toHaveBeenCalledWith('products.publish')
  })
})
