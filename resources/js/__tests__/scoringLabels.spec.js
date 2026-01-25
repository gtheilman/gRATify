import { describe, it, expect } from 'vitest'
import { schemeLabelFor } from '../utils/scoringLabels'

describe('schemeLabelFor', () => {
  it('maps known schemes to labels', () => {
    expect(schemeLabelFor('geometric-decay')).toBe('Geometric decay')
    expect(schemeLabelFor('linear-decay')).toBe('Linear decay')
    expect(schemeLabelFor('linear-decay-with-zeros')).toBe('Linear decay with zeros')
  })
})
