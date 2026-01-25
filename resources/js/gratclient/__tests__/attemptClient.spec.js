import { describe, it, expect, vi, beforeEach } from 'vitest'
import axios from 'axios'
import { postAttemptWithRetry } from '../utils/attemptClient'

vi.mock('axios', () => ({
  default: {
    post: vi.fn()
  }
}))

beforeEach(() => {
  axios.post.mockReset()
})

describe('postAttemptWithRetry', () => {
  it('retries once on failure then succeeds', async () => {
    axios.post
      .mockRejectedValueOnce(new Error('network down'))
      .mockResolvedValueOnce({ data: { ok: true } })

    const response = await postAttemptWithRetry({ answer_id: 1 }, 0)

    expect(response.data.ok).toBe(true)
    expect(axios.post).toHaveBeenCalledTimes(2)
  })

  it('bubbles error after two failures', async () => {
    axios.post.mockRejectedValue(new Error('still failing'))

    await expect(postAttemptWithRetry({ answer_id: 1 }, 0)).rejects.toThrow('still failing')
    expect(axios.post).toHaveBeenCalledTimes(4)
  })
})
