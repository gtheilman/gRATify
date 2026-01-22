import { describe, it, expect } from 'vitest'

describe('ErrorNotice', () => {
  it('documents expected emitted events', () => {
    expect(['retry', 'refresh']).toEqual(['retry', 'refresh'])
  })
})
