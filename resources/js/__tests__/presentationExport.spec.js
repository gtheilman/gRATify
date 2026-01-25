import { describe, it, expect } from 'vitest'
import { buildTimelineHtml } from '../utils/presentationExport'

describe('buildTimelineHtml', () => {
  it('renders a printable html document', () => {
    const html = buildTimelineHtml({
      presentations: [{ user_id: 'user-1', score: 90, assessment: { questions: [] } }],
      assessmentTitle: 'Test Assessment',
      scoringScheme: 'geometric-decay',
    })
    expect(html).toContain('<!doctype html>')
    expect(html).toContain('Test Assessment')
  })

  it('includes the scoring scheme label', () => {
    const html = buildTimelineHtml({
      presentations: [],
      assessmentTitle: 'Assessment',
      scoringScheme: 'linear-decay',
    })
    expect(html).toContain('Linear decay')
  })

  it('shows no scores message when empty', () => {
    const html = buildTimelineHtml({
      presentations: [],
      assessmentTitle: 'Assessment',
      scoringScheme: 'geometric-decay',
    })
    expect(html).toContain('No questions')
  })

  it('shows no attempts when question has none', () => {
    const html = buildTimelineHtml({
      presentations: [
        {
          user_id: 'user-1',
          score: 90,
          assessment: { questions: [{ stem: 'Q1', score: 100, attempts: [] }] },
        },
      ],
      assessmentTitle: 'Assessment',
      scoringScheme: 'geometric-decay',
    })
    expect(html).toContain('No attempts')
  })

  it('shows no questions when assessment is missing', () => {
    const html = buildTimelineHtml({
      presentations: [{ user_id: 'user-1', score: 90 }],
      assessmentTitle: 'Assessment',
      scoringScheme: 'geometric-decay',
    })
    expect(html).toContain('No questions')
  })

  it('escapes html in question stems and answers', () => {
    const html = buildTimelineHtml({
      assessmentTitle: 'Assessment',
      scoringScheme: 'linear-decay',
      presentations: [{
        user_id: '<b>u1</b>',
        score: 100,
        assessment: {
          questions: [{
            stem: '<script>alert(1)</script>',
            score: 100,
            attempts: [{
              created_at: '2024-01-02T03:04:05Z',
              answer: { answer_text: '<img src=x onerror=alert(1)>' },
            }],
          }],
        },
      }],
    })

    expect(html).toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
    expect(html).toContain('&lt;img src=x onerror=alert(1)&gt;')
    expect(html).toContain('User ID: &lt;b&gt;u1&lt;/b&gt;')
  })
})
