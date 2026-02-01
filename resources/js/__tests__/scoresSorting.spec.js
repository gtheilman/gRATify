import { describe, it, expect } from 'vitest'
import { sortPresentations } from '../utils/scoresSorting'

describe('sortPresentations', () => {
  it('sorts by score ascending and descending', () => {
    const list = [{ score: 50 }, { score: 90 }]

    expect(sortPresentations(list, 'score_desc')[0].score).toBe(90)
    expect(sortPresentations(list, 'score_asc')[0].score).toBe(50)
  })

  it('sorts by numeric user id when available', () => {
    const list = [{ user_id: '10' }, { user_id: '2' }]
    const sorted = sortPresentations(list, 'user')

    expect(sorted[0].user_id).toBe('2')
  })

  it('sorts by string user id when non-numeric', () => {
    const list = [{ user_id: 'b' }, { user_id: 'a' }]
    const sorted = sortPresentations(list, 'user')

    expect(sorted[0].user_id).toBe('a')
  })
})
