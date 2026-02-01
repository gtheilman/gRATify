import { describe, it, expect } from 'vitest'
import { defaultPageSizeOptions } from '../utils/pageSizeOptions'

describe('defaultPageSizeOptions', () => {
  it('includes standard size choices', () => {
    expect(defaultPageSizeOptions.map(option => option.value)).toEqual([10, 25, 50, 'all'])
  })

  it('includes labels for all options', () => {
    expect(defaultPageSizeOptions.every(option => typeof option.label === 'string')).toBe(true)
  })

  it('ensures values are unique', () => {
    const values = defaultPageSizeOptions.map(option => option.value)

    expect(new Set(values).size).toBe(values.length)
  })

  it('includes an all option', () => {
    expect(defaultPageSizeOptions.some(option => option.value === 'all')).toBe(true)
  })
})
