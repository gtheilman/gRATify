import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

const shared = vi.hoisted(() => ({
  pending: [],
  axiosPost: vi.fn(),
  now: 0,
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

describe('attempt queue rate-limit cooldown', () => {
  beforeEach(() => {
    shared.pending = [
      { id: 'p|1', presentationKey: 'p', presentationId: 1, answerId: 11, status: 'pending' },
    ]
    shared.now = 1_000
    shared.axiosPost.mockReset()
    vi.resetModules()
    vi.spyOn(Date, 'now').mockImplementation(() => shared.now)
    vi.spyOn(Math, 'random').mockReturnValue(0)
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('waits before retrying after a 429 response', async () => {
    shared.axiosPost.mockRejectedValue({ response: { status: 429 } })

    const { syncQueue, getQueueState } = await import('../utils/attemptQueue')

    await syncQueue('p', { timeoutMs: 3000, maxConcurrent: 10 })
    const firstState = getQueueState('p')

    expect(firstState.rateLimitBackoffMs).toBe(1000)
    expect(firstState.rateLimitCooldownUntil).toBe(2000)
    expect(shared.axiosPost).toHaveBeenCalledTimes(1)

    shared.now = 1_500
    await syncQueue('p', { timeoutMs: 3000, maxConcurrent: 10 })
    expect(shared.axiosPost).toHaveBeenCalledTimes(1)
  })
})
