import { describe, it, expect, vi, afterEach } from 'vitest'
import { buildCsvBlob } from '../utils/csvDownload'

describe('csvDownload', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('builds a CSV blob with the correct type', () => {
    const blob = buildCsvBlob('a,b')

    expect(blob).not.toBeNull()
    expect(blob.type).toContain('text/csv')
  })

  it('returns null when blob creation fails', () => {
    vi.stubGlobal('Blob', class BrokenBlob {
      constructor () {
        throw new Error('no blob')
      }
    })

    expect(buildCsvBlob('a,b')).toBeNull()
  })
})
