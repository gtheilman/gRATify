import { describe, it, expect } from 'vitest'
import { validateChangePasswordForm } from '../utils/changePasswordValidation'

describe('validateChangePasswordForm', () => {
  it('requires authentication', () => {
    expect(validateChangePasswordForm({}, false)).toBe('You must be logged in.')
  })

  it('requires all fields', () => {
    expect(validateChangePasswordForm({ old_password: '', new_password: 'a', new_password_confirmation: 'a' }, true))
      .toBe('Please fill in all fields.')
  })

  it('requires matching passwords', () => {
    expect(validateChangePasswordForm({ old_password: 'old', new_password: 'a', new_password_confirmation: 'b' }, true))
      .toBe('New passwords do not match.')
  })

  it('returns empty string when valid', () => {
    expect(validateChangePasswordForm({ old_password: 'old', new_password: 'a', new_password_confirmation: 'a' }, true))
      .toBe('')
  })

  it('requires old password', () => {
    expect(validateChangePasswordForm({ old_password: '', new_password: 'a', new_password_confirmation: 'a' }, true))
      .toBe('Please fill in all fields.')
  })
})
