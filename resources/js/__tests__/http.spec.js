import { describe, it, expect, vi, afterEach } from 'vitest'
import { fetchJson, fetchJsonOrThrow, fetchBlobOrThrow, buildHttpError } from '../utils/http'

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('http utilities', () => {
  it('returns parsed json for successful responses', async () => {
    const payload = { ok: true }
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
      new Response(JSON.stringify(payload), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    ))

    const { data } = await fetchJsonOrThrow('/api/test')
    expect(data).toEqual(payload)
  })

  it('throws error with status and envelope message', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
      new Response(JSON.stringify({ error: { message: 'Forbidden' } }), {
        status: 403,
        headers: { 'Content-Type': 'application/json' },
      }),
    ))

    await expect(fetchJsonOrThrow('/api/test')).rejects.toMatchObject({
      status: 403,
      message: 'Forbidden',
    })
  })

  it('builds fallback message when no payload is provided', () => {
    const error = buildHttpError({ status: 404 }, null)
    expect(error.message).toBe('Not found.')
    expect(error.status).toBe(404)
  })

  it('returns plain text when parseText is enabled for non-json responses', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
      new Response('plain text', {
        status: 200,
        headers: { 'Content-Type': 'text/plain' },
      }),
    ))

    const { data, text } = await fetchJson('/api/test', { parseText: true })
    expect(data).toBeNull()
    expect(text).toBe('plain text')
  })

  it('returns null data for 204 responses', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
      new Response(null, { status: 204 }),
    ))

    const { data, response } = await fetchJson('/api/test')
    expect(response.ok).toBe(true)
    expect(data).toBeNull()
  })

  it('throws with parsed json when blob request fails', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
      new Response(JSON.stringify({ error: { message: 'Validation failed.' } }), {
        status: 422,
        headers: { 'Content-Type': 'application/json' },
      }),
    ))

    await expect(fetchBlobOrThrow('/api/blob')).rejects.toMatchObject({
      status: 422,
      message: 'Validation failed.',
    })
  })
})
