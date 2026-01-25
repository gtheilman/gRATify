import { describe, it, expect } from 'vitest'
import { canExportScores } from '../utils/scoreTable'

describe('score table helpers', () => {
  it('enables export when presentations exist', () => {
    expect(canExportScores([{ id: 1 }])).toBe(true)
  })

  it('disables export when empty', () => {
    expect(canExportScores([])).toBe(false)
  })

  it('disables export when null', () => {
    expect(canExportScores(null)).toBe(false)
  })

  it('enables export when array has items', () => {
    expect(canExportScores([{}])).toBe(true)
  })
})
