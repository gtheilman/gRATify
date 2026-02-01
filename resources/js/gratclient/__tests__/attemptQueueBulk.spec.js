import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

const makePending = () => ([
  {
    id: 'p|1',
    presentationKey: 'p',
    presentationId: 1,
    answerId: 11,
    status: 'pending',
  },
  {
    id: 'p|2',
    presentationKey: 'p',
    presentationId: 1,
    answerId: 12,
    status: 'pending',
  },
])

const shared = vi.hoisted(() => ({
  pending: [],
  axiosPost: vi.fn(),
}))

vi.mock('axios', () => ({
  default: { post: shared.axiosPost },
}))

vi.mock('../../utils/http', () => ({
  ensureCsrfCookie: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('../utils/idb', () => ({
  STORES: { attempts: 'attempt_queue' },
  idbGetAllByIndex: vi.fn(() => Promise.resolve(shared.pending)),
  idbGet: vi.fn((store, key) => Promise.resolve(shared.pending.find(item => item.id === key) || null)),
  idbDelete: vi.fn((store, key) => {
    shared.pending = shared.pending.filter(item => item.id !== key)
    
    return Promise.resolve()
  }),
  idbSet: vi.fn(),
}))

describe('attempt queue bulk sync', () => {

  beforeEach(() => {
    shared.pending = makePending()
    shared.axiosPost.mockReset()
    vi.resetModules()
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  it('uses bulk endpoint and clears pending attempts', async () => {
    shared.axiosPost.mockResolvedValueOnce({
      data: {
        results: [
          { presentation_id: 1, answer_id: 11, status: 'created' },
          { presentation_id: 1, answer_id: 12, status: 'created' },
        ],
      },
    })

    const { syncQueue } = await import('../utils/attemptQueue')

    await syncQueue('p', { timeoutMs: 3000, maxConcurrent: 10 })

    expect(shared.axiosPost).toHaveBeenCalledTimes(1)
    expect(shared.axiosPost.mock.calls[0][0]).toBe('/api/attempts/bulk')
    expect(shared.pending).toHaveLength(0)
  })

  it('falls back to per-attempt when bulk fails', async () => {
    shared.axiosPost
      .mockRejectedValueOnce({ response: { status: 401 } })
      .mockResolvedValue({ data: { correct: true } })

    const { syncQueue } = await import('../utils/attemptQueue')

    await syncQueue('p', { timeoutMs: 3000, maxConcurrent: 10 })

    expect(shared.axiosPost).toHaveBeenCalled()
    expect(shared.axiosPost.mock.calls[0][0]).toBe('/api/attempts/bulk')
    expect(shared.axiosPost.mock.calls[1][0]).toBe('/api/attempts')
    expect(shared.axiosPost.mock.calls[2][0]).toBe('/api/attempts')
  })
})
