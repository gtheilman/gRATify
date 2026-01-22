import { describe, it, expect } from 'vitest'
import { progressBarColor } from '../utils/progressBar'

describe('progressBarColor', () => {
  it('maps percent ranges to color tokens', () => {
    expect(progressBarColor(10)).toBe('error')
    expect(progressBarColor(30)).toBe('warning')
    expect(progressBarColor(50)).toBe('secondary')
    expect(progressBarColor(70)).toBe('primary')
    expect(progressBarColor(90)).toBe('success')
  })

  it('handles boundary values', () => {
    expect(progressBarColor(0)).toBe('error')
    expect(progressBarColor(80)).toBe('success')
  })

  it('treats 20 as warning boundary', () => {
    expect(progressBarColor(20)).toBe('warning')
  })

  it('treats 40 as secondary boundary', () => {
    expect(progressBarColor(40)).toBe('secondary')
  })

  it('treats 60 as primary boundary', () => {
    expect(progressBarColor(60)).toBe('primary')
  })

  it('treats 79 as primary and 80 as success', () => {
    expect(progressBarColor(79)).toBe('primary')
    expect(progressBarColor(80)).toBe('success')
  })
})
