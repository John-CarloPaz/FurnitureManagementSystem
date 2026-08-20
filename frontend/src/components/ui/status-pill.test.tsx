import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { StatusPill } from './status-pill'

describe('StatusPill', () => {
  it('renders the human label for a state', () => {
    render(<StatusPill state="READY_FOR_DELIVERY" />)
    expect(screen.getByText('Ready for Delivery')).toBeInTheDocument()
  })
})
