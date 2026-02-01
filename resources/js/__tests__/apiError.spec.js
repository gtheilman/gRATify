import { describe, it, expect } from 'vitest'
import { extractApiErrorMessage, readApiErrorDetail, getErrorMessage } from '../utils/apiError'

describe('apiError utilities', () => {
  it('extracts error.message when present', () => {
    const message = extractApiErrorMessage({ error: { message: 'Forbidden' } })

    expect(message).toBe('Forbidden')
  })

  it('falls back to message/status fields', () => {
    expect(extractApiErrorMessage({ message: 'Not Found' })).toBe('Not Found')
    expect(extractApiErrorMessage({ status: 'Locked' })).toBe('Locked')
  })

  it('prefers error.message over top-level message', () => {
    const message = extractApiErrorMessage({ error: { message: 'Nested' }, message: 'Top' })

    expect(message).toBe('Nested')
  })

  it('extracts validation errors from array payloads', () => {
    const message = extractApiErrorMessage({ errors: ['First error', 'Second error'] })

    expect(message).toBe('First error')
  })

  it('extracts validation errors from object payloads', () => {
    const message = extractApiErrorMessage({ errors: { field: ['Field error'] } })

    expect(message).toBe('Field error')
  })

  it('returns empty string for empty payload', () => {
    expect(extractApiErrorMessage(null)).toBe('')
  })

  it('reads error detail from json responses', async () => {
    const response = new Response(JSON.stringify({ error: { message: 'Unauthorized' } }), {
      status: 401,
      headers: { 'Content-Type': 'application/json' },
    })

    await expect(readApiErrorDetail(response)).resolves.toBe('Unauthorized')
  })

  it('reads detail from text responses', async () => {
    const response = new Response('plain error', {
      status: 500,
      headers: { 'Content-Type': 'text/plain' },
    })

    await expect(readApiErrorDetail(response)).resolves.toBe('plain error')
  })

  it('returns json string when no message fields exist', async () => {
    const response = new Response(JSON.stringify({ foo: 'bar' }), {
      status: 400,
      headers: { 'Content-Type': 'application/json' },
    })

    await expect(readApiErrorDetail(response)).resolves.toBe('{"foo":"bar"}')
  })

  it('returns fallback for object errors without message', () => {
    const message = getErrorMessage({ foo: 'bar' }, 'Fallback message')

    expect(message).toBe('Fallback message')
  })

  it('avoids [object Object] messages', () => {
    const message = getErrorMessage({ message: '[object Object]', error: { message: 'Readable' } }, 'Fallback')

    expect(message).toBe('Readable')
  })
})
