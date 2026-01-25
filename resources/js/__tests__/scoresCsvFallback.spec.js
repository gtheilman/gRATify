import { describe, it, expect, vi } from 'vitest'
import { buildCsvBlob } from '../utils/csvDownload'
import { buildScoresCsv } from '../utils/scoresCsv'

describe('scores CSV fallback', () => {
  it('returns payload when blob creation fails', () => {
    const originalBlob = globalThis.Blob
    globalThis.Blob = vi.fn(() => { throw new Error('blob failed') })

    const payload = buildScoresCsv([{ user_id: 'u1', score: 95 }], v => String(v))
    const blob = buildCsvBlob(payload)

    expect(blob).toBeNull()
    expect(payload).toBe('UserID,Score\r\nu1,95')

    globalThis.Blob = originalBlob
  })

  it('returns blob when creation succeeds', () => {
    const payload = buildScoresCsv([{ user_id: 'u2', score: 88 }], v => String(v))
    const blob = buildCsvBlob(payload)
    expect(blob).not.toBeNull()
  })
})
