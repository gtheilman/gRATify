import { describe, it, expect } from 'vitest'
import { extractPasswordStatus } from '../utils/changePassword'

describe('extractPasswordStatus', () => {
  it('prefers status on data payload', async () => {
    const status = await extractPasswordStatus({ status: 'ok' }, null)

    expect(status).toBe('ok')
  })

  it('reads status from error data', async () => {
    const status = await extractPasswordStatus(null, { data: { status: 'invalid_old_password' } })

    expect(status).toBe('invalid_old_password')
  })

  it('falls back to response json status', async () => {
    const error = {
      response: {
        clone: () => ({
          json: async () => ({ status: 'invalid_old_password' }),
        }),
      },
    }

    const status = await extractPasswordStatus(null, error)

    expect(status).toBe('invalid_old_password')
  })
})
