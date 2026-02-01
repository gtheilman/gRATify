import { describe, it, expect } from 'vitest'
import { defaultScoringScheme, scoringSchemeOptions } from '../utils/scoringSchemes'

describe('scoring scheme options', () => {
  it('includes all supported schemes', () => {
    const values = scoringSchemeOptions.map(option => option.value)

    expect(values).toEqual([
      'geometric-decay',
      'linear-decay',
      'linear-decay-with-zeros',
    ])
  })

  it('sets the geometric decay default', () => {
    expect(defaultScoringScheme).toBe('geometric-decay')
  })
})
