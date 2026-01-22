import { describe, it, expect } from 'vitest'
import { buildSequenceSwap } from '../utils/sequenceSwap'

describe('buildSequenceSwap', () => {
  const items = [
    { id: 1, sequence: 1 },
    { id: 2, sequence: 2 },
    { id: 3, sequence: 3 },
  ]

  it('returns null when no swap is possible', () => {
    expect(buildSequenceSwap([], 1, 'up')).toBeNull()
    expect(buildSequenceSwap(items, 1, 'up')).toBeNull()
    expect(buildSequenceSwap(items, 3, 'down')).toBeNull()
  })

  it('returns the correct neighbor for upward swaps', () => {
    const swap = buildSequenceSwap(items, 2, 'up')
    expect(swap?.current?.id).toBe(2)
    expect(swap?.neighbor?.id).toBe(1)
    expect(swap?.currentSequence).toBe(2)
    expect(swap?.neighborSequence).toBe(1)
  })

  it('returns the correct neighbor for downward swaps', () => {
    const swap = buildSequenceSwap(items, 2, 'down')
    expect(swap?.current?.id).toBe(2)
    expect(swap?.neighbor?.id).toBe(3)
  })
})
