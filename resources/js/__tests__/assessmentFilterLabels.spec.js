import { describe, it, expect } from 'vitest'
import { getActiveFilterLabel } from '../utils/assessmentFilterLabels'

describe('getActiveFilterLabel', () => {
  it('returns labels for active/inactive', () => {
    expect(getActiveFilterLabel('active')).toBe('Active only')
    expect(getActiveFilterLabel('inactive')).toBe('Inactive only')
  })

  it('defaults to all statuses', () => {
    expect(getActiveFilterLabel('all')).toBe('All statuses')
  })

  it('falls back to all statuses for unknown values', () => {
    expect(getActiveFilterLabel('unknown')).toBe('All statuses')
  })
})
