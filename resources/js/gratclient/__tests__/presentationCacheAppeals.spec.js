import { describe, it, expect, vi } from 'vitest'
import { readPresentationCache, writePresentationCache } from '../utils/presentationCache'

const store = new Map()

vi.mock('../utils/idb', () => ({
  STORES: { presentation: 'presentation' },
  idbGet: vi.fn((_, key) => Promise.resolve(store.get(key) || null)),
  idbSet: vi.fn((_, record) => {
    store.set(record.key, record)
    
    return Promise.resolve()
  }),
  idbDelete: vi.fn((_, key) => {
    store.delete(key)
    
    return Promise.resolve()
  }),
}))

vi.mock('../utils/scramble', () => ({
  scramble: value => value,
  descramble: value => value,
}))

describe('presentation cache appeals', () => {
  it('persists appeals and appeals_open across cache read/write', async () => {
    const payload = {
      assessment: {
        appeals_open: false,
        questions: [
          {
            answers: [
              { correct_scrambled: 'x' },
            ],
          },
        ],
      },
      appeals: [
        { id: 1, question_id: 2, body: 'Appeal text' },
      ],
    }

    await writePresentationCache('pw', 'team1', payload)

    const cached = await readPresentationCache('pw', 'team1')

    expect(cached.assessment.appeals_open).toBe(false)
    expect(cached.appeals).toHaveLength(1)
    expect(cached.appeals[0].body).toBe('Appeal text')
  })
})
