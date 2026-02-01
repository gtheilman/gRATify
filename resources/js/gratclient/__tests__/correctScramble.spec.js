import { describe, it, expect, beforeAll } from 'vitest'
import { decodeCorrectScrambled } from '../utils/correctScramble'

beforeAll(() => {
  if (typeof global.atob === 'undefined') {
    global.atob = value => Buffer.from(value, 'base64').toString('binary')
  }
  if (typeof global.btoa === 'undefined') {
    global.btoa = value => Buffer.from(value, 'binary').toString('base64')
  }
})

const encode = (value, password) => {
  const raw = value ? '1' : '0'
  const mask = password.charCodeAt(0) || 0
  const byte = raw.charCodeAt(0) ^ mask
  
  return btoa(String.fromCharCode(byte))
}

describe('decodeCorrectScrambled', () => {
  it('decodes true/false using the password mask', () => {
    const password = 'abc123'
    const encodedTrue = encode(true, password)
    const encodedFalse = encode(false, password)

    expect(decodeCorrectScrambled(encodedTrue, password)).toBe(true)
    expect(decodeCorrectScrambled(encodedFalse, password)).toBe(false)
  })

  it('returns null for missing inputs', () => {
    expect(decodeCorrectScrambled('', 'pw')).toBeNull()
    expect(decodeCorrectScrambled('abc', '')).toBeNull()
  })
})
