import { describe, it, expect } from 'vitest'
import { escapeHtml, normalizeUserId, truncateText } from '../utils/textFormat'

describe('text format helpers', () => {
  it('truncates long text', () => {
    expect(truncateText('abcdef', 3)).toBe('abc...')
    expect(truncateText('short', 10)).toBe('short')
  })

  it('escapes html special characters', () => {
    expect(escapeHtml('<div>&</div>')).toBe('&lt;div&gt;&amp;&lt;/div&gt;')
  })

  it('escapes non-string values', () => {
    expect(escapeHtml(0)).toBe('0')
    expect(escapeHtml(false)).toBe('false')
  })

  it('returns empty string for nullish values', () => {
    expect(escapeHtml(null)).toBe('')
    expect(escapeHtml(undefined)).toBe('')
  })

  it('normalizes numeric ids', () => {
    expect(normalizeUserId('10')).toBe(10)
    expect(normalizeUserId('abc')).toBe('abc')
  })

  it('normalizes zero and negative ids', () => {
    expect(normalizeUserId('0')).toBe(0)
    expect(normalizeUserId('-5')).toBe(-5)
  })

  it('returns input for non-numeric objects', () => {
    const obj = {}
    expect(normalizeUserId(obj)).toBe(obj)
  })
})
