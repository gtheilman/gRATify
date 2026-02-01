import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { resolveDemoWarningState, readDemoWarningCache, writeDemoWarningCache, applyDemoWarningFallback } from '../utils/demoWarning'

const mockSessionStorage = () => {
  let store = {}
  
  return {
    getItem: key => (key in store ? store[key] : null),
    setItem: (key, value) => { store[key] = String(value) },
    clear: () => { store = {} },
  }
}

describe('demo warning helpers', () => {
  beforeEach(() => {
    vi.stubGlobal('sessionStorage', mockSessionStorage())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('resolves the warning flag from payload', () => {
    expect(resolveDemoWarningState({ showWarning: true })).toBe(true)
    expect(resolveDemoWarningState({ showDemoUsers: true })).toBe(true)
    expect(resolveDemoWarningState({})).toBe(false)
  })

  it('reads and writes cached warning state', () => {
    expect(readDemoWarningCache()).toBeNull()
    writeDemoWarningCache(true)
    expect(readDemoWarningCache()).toBe(true)
    writeDemoWarningCache(false)
    expect(readDemoWarningCache()).toBe(false)
  })

  it('writes and returns the fallback warning state', () => {
    expect(applyDemoWarningFallback()).toBe(true)
    expect(readDemoWarningCache()).toBe(true)
  })

})
