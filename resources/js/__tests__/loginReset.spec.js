import { describe, it, expect } from 'vitest'
import { validateResetEmail } from '../utils/loginReset'

describe('validateResetEmail', () => {
  it('requires an email value', () => {
    expect(validateResetEmail('')).toBe('Enter your email to receive a reset link.')
  })

  it('returns empty string when email is present', () => {
    expect(validateResetEmail('user@example.com')).toBe('')
  })

  it('treats whitespace-only as missing', () => {
    expect(validateResetEmail('   ')).toBe('Enter your email to receive a reset link.')
  })
})
