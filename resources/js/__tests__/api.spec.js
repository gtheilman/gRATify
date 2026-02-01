import { describe, it, expect, vi, beforeEach } from 'vitest'

const { createSpy, getXsrfToken } = vi.hoisted(() => ({
  createSpy: vi.fn(config => config),
  getXsrfToken: vi.fn(),
}))

vi.mock('ofetch', () => {
  return {
    ofetch: {
      create: createSpy,
    },
  }
})

vi.mock('@/utils/csrf', () => {
  return {
    getXsrfToken,
  }
})

import { $api } from '../utils/api'

describe('api client', () => {
  beforeEach(() => {
    getXsrfToken.mockReset()
    createSpy.mockClear()
  })

  it('configures base URL and credentials', () => {
    expect($api.baseURL).toBe('/api')
    expect($api.credentials).toBe('same-origin')
  })

  it('adds XSRF header when token exists', async () => {
    getXsrfToken.mockReturnValue('token')

    const options = { headers: { Existing: 'value' } }

    await $api.onRequest({ options })

    expect(options.headers).toEqual({
      Existing: 'value',
      'X-XSRF-TOKEN': 'token',
    })
  })

  it('skips XSRF header when token is missing', async () => {
    getXsrfToken.mockReturnValue(null)

    const options = { headers: { Existing: 'value' } }

    await $api.onRequest({ options })

    expect(options.headers).toEqual({ Existing: 'value' })
  })
})
