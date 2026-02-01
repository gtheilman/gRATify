import { describe, it, expect, vi, beforeEach } from 'vitest'
import AnswerComponent from '../components/Answer.vue'
import { queueAttempt, syncQueue } from '../utils/attemptQueue'

vi.mock('../utils/attemptQueue', () => ({
  queueAttempt: vi.fn().mockResolvedValue({ id: 'q1' }),
  markAttemptSynced: vi.fn(),
  syncQueue: vi.fn(),
  onQueueEvent: vi.fn(() => () => {}),
}))

vi.mock('../utils/idb', () => ({
  isStorageAvailable: vi.fn().mockResolvedValue(true),
}))

describe('Answer offline behavior', () => {
  beforeEach(() => {
    global.navigator = { onLine: false }
    if (typeof global.atob === 'undefined') {
      global.atob = value => Buffer.from(value, 'base64').toString('binary')
    }
    if (typeof global.btoa === 'undefined') {
      global.btoa = value => Buffer.from(value, 'binary').toString('base64')
    }
  })

  it('marks correct immediately when offline using local data', async () => {
    const password = 'pw'
    const mask = password.charCodeAt(0)
    const byte = '1'.charCodeAt(0) ^ mask
    const correctScrambled = btoa(String.fromCharCode(byte))

    const ctx = {
      answered: false,
      checking: false,
      isCorrect: false,
      isInCorrect: false,
      questionLocked: false,
      answer: { id: 1, correct_scrambled: correctScrambled },
      presentation_id: 10,
      presentationKey: 'pw|student',
      password,
      lastClickAt: null,
      errorMessage: '',
      resultAnnouncement: '',
      retryAvailable: false,
      messageTimer: null,
      messageInterval: null,
      messageIndex: 0,
      $emit: vi.fn(),
      $nextTick: fn => fn(),
      $refs: { resultLive: { focus: vi.fn() } },
      getLocalCorrectFlag: AnswerComponent.methods.getLocalCorrectFlag,
      handleAttemptResponse: AnswerComponent.methods.handleAttemptResponse,
      resetRetryState: AnswerComponent.methods.resetRetryState,
      startSilentRetry: vi.fn(),
      postAttempt: vi.fn().mockResolvedValue({ data: { correct: true } }),
    }

    await AnswerComponent.methods.checkAnswer.call(ctx)

    expect(ctx.isCorrect).toBe(true)
    await Promise.resolve()
    expect(queueAttempt).toHaveBeenCalled()
    expect(syncQueue).toHaveBeenCalled()
    expect(ctx.postAttempt).not.toHaveBeenCalled()
  })
})
