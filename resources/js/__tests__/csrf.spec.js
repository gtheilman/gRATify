import { describe, it, expect, vi, beforeEach } from 'vitest'

let cookieValue

vi.mock('universal-cookie', () => {
  return {
    default: class Cookies {
      get () {
        return cookieValue
      }
    },
  }
})

import { getXsrfToken } from '../utils/csrf'

describe('csrf utils', () => {
  beforeEach(() => {
    cookieValue = undefined
  })

  it('returns null when cookie is missing', () => {
    expect(getXsrfToken()).toBeNull()
  })

  it('decodes the XSRF cookie value', () => {
    cookieValue = 'abc%20123'
    expect(getXsrfToken()).toBe('abc 123')
  })
})
