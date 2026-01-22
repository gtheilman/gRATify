import { describe, it, expect } from 'vitest'
import { formatAssessmentError } from '../utils/assessmentErrors'

describe('formatAssessmentError', () => {
  it('uses context-specific forbidden messages', () => {
    const response = { status: 403 }
    expect(formatAssessmentError(response, null, 'progress')).toBe('Forbidden: you do not have access to this progress view.')
    expect(formatAssessmentError(response, null, 'feedback')).toBe('Forbidden: you do not have access to this feedback view.')
    expect(formatAssessmentError(response, null, 'scores')).toBe('Forbidden: you do not have access to these scores.')
  })

  it('returns fallback for unknown status', () => {
    const response = { status: 418 }
    expect(formatAssessmentError(response, null, 'scores')).toBe('Unable to load scores')
  })

  it('includes detail when provided', () => {
    const response = { status: 403 }
    expect(formatAssessmentError(response, { error: { message: 'No access' } }, 'scores'))
      .toBe('Forbidden: No access')
  })

  it('handles server errors with default message', () => {
    const response = { status: 500 }
    expect(formatAssessmentError(response, null, 'feedback')).toBe('Server error: unable to load feedback right now.')
  })

  it('handles unauthorized errors', () => {
    const response = { status: 401 }
    expect(formatAssessmentError(response, null, 'scores')).toBe('Unauthorized: please sign in again.')
  })
})
