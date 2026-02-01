import { describe, it, expect } from 'vitest'
import { applyApiError } from '../composables/useApi'

describe('applyApiError', () => {
  it('uses error envelope message when available', async () => {
    const response = new Response(JSON.stringify({ error: { message: 'Forbidden' } }), {
      status: 403,
      headers: { 'Content-Type': 'application/json' },
    })

    const ctx = { response, error: new Error('Failed to fetch') }

    await applyApiError(ctx)

    expect(ctx.error.message).toBe('Forbidden')
    expect(ctx.error.status).toBe(403)
    expect(ctx.error.data?.error?.message).toBe('Forbidden')
  })

  it('falls back to status-based message when no envelope', async () => {
    const response = new Response('nope', {
      status: 404,
      headers: { 'Content-Type': 'text/plain' },
    })

    const ctx = { response, error: new Error('Failed to fetch') }

    await applyApiError(ctx)

    expect(ctx.error.message).toBe('Not found.')
    expect(ctx.error.status).toBe(404)
  })

  it('surfaces validation errors when present', async () => {
    const response = new Response(JSON.stringify({ errors: { name: ['Name is required'] } }), {
      status: 422,
      headers: { 'Content-Type': 'application/json' },
    })

    const ctx = { response, error: new Error('Failed to fetch') }

    await applyApiError(ctx)

    expect(ctx.error.message).toBe('Name is required')
    expect(ctx.error.status).toBe(422)
  })

  it('uses status fallback for 401 when no envelope message is provided', async () => {
    const response = new Response('', {
      status: 401,
      headers: { 'Content-Type': 'text/plain' },
    })

    const ctx = { response, error: new Error('Failed to fetch') }

    await applyApiError(ctx)

    expect(ctx.error.message).toBe('Unauthorized: please sign in again.')
    expect(ctx.error.status).toBe(401)
  })

  it('uses error envelope message for locked responses', async () => {
    const response = new Response(JSON.stringify({ error: { message: 'Locked' } }), {
      status: 423,
      headers: { 'Content-Type': 'application/json' },
    })

    const ctx = { response, error: new Error('Failed to fetch') }

    await applyApiError(ctx)

    expect(ctx.error.message).toBe('Locked')
    expect(ctx.error.status).toBe(423)
  })
})
