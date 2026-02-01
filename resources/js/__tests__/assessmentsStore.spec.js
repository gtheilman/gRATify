import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

const mockApi = vi.fn()

vi.mock('@/composables/useApi', () => {
  return {
    useApi: (...args) => mockApi(...args),
  }
})

const mockStorage = () => {
  let store = {}
  
  return {
    getItem: key => (key in store ? store[key] : null),
    setItem: (key, value) => { store[key] = String(value) },
    removeItem: key => { delete store[key] },
    clear: () => { store = {} },
  }
}

describe('assessments store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('localStorage', mockStorage())
    mockApi.mockReset()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.resetModules()
  })

  it('normalizes answer correctness to booleans on load', async () => {
    const { useAssessmentsStore } = await import('@/stores/assessments')
    const store = useAssessmentsStore()

    mockApi.mockResolvedValue({
      data: {
        value: {
          id: 1,
          questions: [
            { id: 10, answers: [{ id: 100, correct: 1 }, { id: 101, correct: 0 }] },
          ],
        },
      },
      error: { value: null },
    })

    await store.loadAssessment(1)

    const answers = store.currentAssessment.questions[0].answers

    expect(answers[0].correct).toBe(true)
    expect(answers[1].correct).toBe(false)
  })

  it('persists short link provider preference', async () => {
    const { useAssessmentsStore } = await import('@/stores/assessments')
    const store = useAssessmentsStore()

    store.setShortLinkProvider('tinyurl')

    expect(globalThis.localStorage.getItem('shortLinkProvider')).toBe('tinyurl')
    expect(store.shortLinkProvider).toBe('tinyurl')
  })
})
