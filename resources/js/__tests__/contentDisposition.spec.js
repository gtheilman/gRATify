import { describe, it, expect } from 'vitest'
import { parseFilenameFromDisposition } from '../utils/contentDisposition'

describe('parseFilenameFromDisposition', () => {
  it('extracts filename from content disposition header', () => {
    const header = 'attachment; filename="backup.sql.gz"'
    expect(parseFilenameFromDisposition(header, 'fallback.sql')).toBe('backup.sql.gz')
  })

  it('returns fallback when header is missing', () => {
    expect(parseFilenameFromDisposition('', 'fallback.sql')).toBe('fallback.sql')
  })

  it('handles unquoted filenames', () => {
    const header = 'attachment; filename=backup.sql.gz'
    expect(parseFilenameFromDisposition(header, 'fallback.sql')).toBe('backup.sql.gz')
  })

  it('handles null headers', () => {
    expect(parseFilenameFromDisposition(null, 'fallback.sql')).toBe('fallback.sql')
  })

  it('returns fallback when filename is missing', () => {
    expect(parseFilenameFromDisposition('attachment', 'fallback.sql')).toBe('fallback.sql')
  })

  it('ignores encoded filename params', () => {
    const header = "attachment; filename*=UTF-8''backup.sql.gz"
    expect(parseFilenameFromDisposition(header, 'fallback.sql')).toBe('fallback.sql')
  })

  it('handles extra whitespace around filename', () => {
    const header = 'attachment;  filename = "report.csv"'
    expect(parseFilenameFromDisposition(header, 'fallback.csv')).toBe('report.csv')
  })
})
