import { describe, it, expect } from 'vitest'
import { normalizeAikenMessage, buildAikenMessages } from '../utils/aikenErrors'

describe('aiken error helpers', () => {
  it('normalizes error messages and strips prefixes', () => {
    const msg = 'Error: questionNotCompleteOn line 4'

    expect(normalizeAikenMessage(msg)).toBe('Question not complete on line 4')
  })

  it('returns fallback when no valid errors are present', () => {
    const messages = buildAikenMessages({}, '')

    expect(messages[0]).toBe('Invalid Aiken format text file.')
  })

  it('ignores non-error strings when requiring error prefix', () => {
    expect(normalizeAikenMessage('Notice: hello')).toBeNull()
  })
})
