import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

const shared = vi.hoisted(() => ({
  pending: [],
  axiosPost: vi.fn(),
  now: 0,
}))

vi.mock('axios', () => ({
  default: { post: shared.axiosPost },
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

describe('attempt queue adaptive timeout', () => {

  beforeEach(() => {
    shared.pending = [
      { id: 'p|1', presentationKey: 'p', presentationId: 1, answerId: 11, status: 'pending' },
      { id: 'p|2', presentationKey: 'p', presentationId: 1, answerId: 12, status: 'pending' },
    ]
    shared.now = 0
    shared.axiosPost.mockReset()
    vi.resetModules()
    vi.spyOn(Date, 'now').mockImplementation(() => shared.now)
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('increases timeout based on slow batch duration', async () => {
    shared.axiosPost.mockImplementation(() => {
      shared.now += 2000
      return Promise.resolve({
        data: {
          results: [
            { presentation_id: 1, answer_id: 11, status: 'created' },
            { presentation_id: 1, answer_id: 12, status: 'created' },
          ],
        },
      })
    })

    const { syncQueue, getQueueState } = await import('../utils/attemptQueue')

    await syncQueue('p', { timeoutMs: 3000, maxConcurrent: 10 })

    const state = getQueueState('p')
    expect(state.timeoutMs).toBeGreaterThanOrEqual(4000)
    expect(state.emaMs).toBeGreaterThanOrEqual(2000)
  })
})
