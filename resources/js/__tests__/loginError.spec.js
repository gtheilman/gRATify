import { describe, it, expect } from 'vitest'
import { formatLoginError } from '../utils/loginError'

describe('formatLoginError', () => {
  it('returns fallback when error message is not usable', () => {
    expect(formatLoginError({ message: '[object Object]' })).toBe('Login failed')
  })

  it('uses error message when available', () => {
    expect(formatLoginError({ message: 'Invalid credentials' })).toBe('Invalid credentials')
  })

  it('falls back when message is empty', () => {
    expect(formatLoginError({ message: '' })).toBe('Login failed')
  })
})
