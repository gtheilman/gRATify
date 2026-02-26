import { describe, it, expect, vi, beforeEach } from 'vitest'
import fs from 'fs'
import path from 'path'
import AssessmentComponent from '../components/Assessment.vue'

vi.mock('../utils/http', () => ({
  ensureCsrfCookie: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('../utils/presentationCache', () => ({
  writePresentationCache: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('axios', () => ({
  default: { post: vi.fn(), get: vi.fn() },
}))

import axios from 'axios'
import { writePresentationCache } from '../utils/presentationCache'

describe('Appeals', () => {
  beforeEach(() => {
    global.fetch = vi.fn().mockResolvedValue({})
    axios.post.mockReset()
    axios.get.mockReset()
    writePresentationCache.mockReset()
  })

  it('hides the appeal button when appeals are closed', () => {
    const filePath = path.resolve(__dirname, '../components/Assessment.vue')
    const content = fs.readFileSync(filePath, 'utf8')

    expect(content).toContain('v-if="appealsOpen"')
  })

  it('submits an appeal and locks further edits', async () => {
    const ctx = {
      appealDraft: 'Reason',
      appealSubmitting: false,
      appealStatus: '',
      appealError: '',
      appealDraftTrimmed: 'Reason',
      appealLocked: false,
      activeQuestionId: 22,
      presentation: { id: 9, appeals: [] },
      password: 'abc',
      user_id: 'team1',
      showAppealModal: true,
      syncActiveQuestionFromSwiper: vi.fn(),
      ensureCsrfCookie: vi.fn().mockResolvedValue(undefined),
    }

    axios.post.mockResolvedValue({ data: { id: 1, question_id: 22, body: 'Reason' } })

    await AssessmentComponent.methods.submitAppeal.call(ctx)

    expect(axios.post).toHaveBeenCalledWith('/api/appeals', {
      presentation_id: 9,
      question_id: 22,
      body: 'Reason',
    })
    expect(ctx.appealStatus).toBe('Saved on server.')
    expect(ctx.appealError).toBe('')
    expect(ctx.showAppealModal).toBe(false)
    expect(writePresentationCache).toHaveBeenCalledWith('abc', 'team1', ctx.presentation)
  })

  it('refreshes appeals state and updates cached presentation', async () => {
    const ctx = {
      appealRefreshInFlight: false,
      password: 'abc',
      user_id: 'team1',
      presentation: {
        assessment: { appeals_open: true },
        appeals: [],
      },
    }

    axios.get.mockResolvedValue({
      data: {
        assessment: { appeals_open: false },
        appeals: [{ id: 5, question_id: 11, body: 'Saved' }],
      },
    })

    await AssessmentComponent.methods.refreshAppealsState.call(ctx)

    expect(axios.get).toHaveBeenCalledWith('/api/presentations/store/abc/team1')
    expect(ctx.presentation.assessment.appeals_open).toBe(false)
    expect(ctx.presentation.appeals).toHaveLength(1)
    expect(writePresentationCache).toHaveBeenCalledWith('abc', 'team1', ctx.presentation)
  })

  it('preserves draft appeal while modal is open', () => {
    const ctx = {
      showAppealModal: true,
      appealDraft: 'Working draft',
      appealDraftTrimmed: 'Working draft',
      appealStatus: '',
      appealError: '',
      activeQuestionId: 22,
      presentation: { appeals: [] },
    }

    AssessmentComponent.methods.syncAppealDraft.call(ctx)

    expect(ctx.appealDraft).toBe('Working draft')
  })

  it('does not submit when appeals are locked', async () => {
    const ctx = {
      appealDraft: 'Reason',
      appealSubmitting: false,
      appealStatus: '',
      appealError: '',
      appealLocked: true,
      appealDraftTrimmed: 'Reason',
      activeQuestionId: 22,
      presentation: { id: 9, appeals: [] },
      showAppealModal: true,
      syncActiveQuestionFromSwiper: vi.fn(),
      ensureCsrfCookie: vi.fn().mockResolvedValue(undefined),
    }

    await AssessmentComponent.methods.submitAppeal.call(ctx)

    expect(axios.post).not.toHaveBeenCalled()
  })

  it('stops appeals polling when refresh gets 401', async () => {
    const ctx = {
      appealRefreshInFlight: false,
      password: 'abc',
      user_id: 'team1',
      presentation: {
        assessment: { appeals_open: true },
        appeals: [],
      },
      stopAppealsPolling: vi.fn(),
    }

    axios.get.mockRejectedValue({ response: { status: 401 } })

    await AssessmentComponent.methods.refreshAppealsState.call(ctx)

    expect(ctx.stopAppealsPolling).toHaveBeenCalledTimes(1)
    expect(writePresentationCache).not.toHaveBeenCalled()
    expect(ctx.appealRefreshInFlight).toBe(false)
  })

  it('stops appeals polling when refresh gets 403', async () => {
    const ctx = {
      appealRefreshInFlight: false,
      password: 'abc',
      user_id: 'team1',
      presentation: {
        assessment: { appeals_open: true },
        appeals: [],
      },
      stopAppealsPolling: vi.fn(),
    }

    axios.get.mockRejectedValue({ response: { status: 403 } })

    await AssessmentComponent.methods.refreshAppealsState.call(ctx)

    expect(ctx.stopAppealsPolling).toHaveBeenCalledTimes(1)
    expect(writePresentationCache).not.toHaveBeenCalled()
    expect(ctx.appealRefreshInFlight).toBe(false)
  })

  it('keeps appeals polling on transient refresh failures', async () => {
    const ctx = {
      appealRefreshInFlight: false,
      password: 'abc',
      user_id: 'team1',
      presentation: {
        assessment: { appeals_open: true },
        appeals: [],
      },
      stopAppealsPolling: vi.fn(),
    }

    axios.get.mockRejectedValue({ response: { status: 500 } })

    await AssessmentComponent.methods.refreshAppealsState.call(ctx)

    expect(ctx.stopAppealsPolling).not.toHaveBeenCalled()
    expect(ctx.appealRefreshInFlight).toBe(false)
  })
})
