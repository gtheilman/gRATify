import { describe, it, expect } from 'vitest'
import { usersEmptyStateMessage } from '../utils/usersEmptyState'

describe('users empty state message', () => {
  it('includes a hint about seeded accounts', () => {
    expect(usersEmptyStateMessage).toContain('Seeded accounts')
  })
})
