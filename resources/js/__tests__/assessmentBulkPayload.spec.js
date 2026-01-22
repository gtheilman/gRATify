import { describe, it, expect } from 'vitest'
import { buildAssessmentBulkPayload } from '../utils/assessmentBulkPayload'

describe('buildAssessmentBulkPayload', () => {
  it('trims titles and maps nested answers', () => {
    const payload = buildAssessmentBulkPayload(
      { id: 1, title: 'Test' },
      [
        {
          id: 10,
          title: '  ',
          stem: 'Stem',
          sequence: 1,
          answers: [{ id: 100, answer_text: 'A', correct: true, sequence: 1 }],
        },
      ],
    )

    expect(payload.assessment.id).toBe(1)
    expect(payload.questions[0].title).toBe('Stem')
    expect(payload.questions[0].answers[0].answer_text).toBe('A')
  })
})
