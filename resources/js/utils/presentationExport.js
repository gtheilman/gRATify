import { schemeLabelFor } from '@/utils/scoringLabels'
import { formatTimestamp } from '@/utils/dateFormat'
import { escapeHtml, truncateText } from '@/utils/textFormat'

export const buildTimelineHtml = ({ presentations = [], assessmentTitle = '', scoringScheme }) => {
  const schemeLabel = schemeLabelFor(scoringScheme)
  const printedAt = formatTimestamp(new Date().toISOString())

  const rows = presentations.map(presentation => {
    const questions = (presentation.assessment?.questions || []).map(question => {
      const attempts = (question.attempts || []).map(attempt => {
        const timestamp = escapeHtml(formatTimestamp(attempt.created_at))
        const answer = escapeHtml(truncateText(attempt.answer?.answer_text))
        
        return `<div class="attempt-row"><span class="attempt-time">${timestamp}</span><span>${answer}</span></div>`
      }).join('')

      return `
        <div class="question-block">
          <div class="question-stem">${escapeHtml(question.stem)}</div>
          <div class="question-score">Score ${escapeHtml(question.score)}%</div>
          <div class="attempts">${attempts || '<div class="attempt-row empty">No attempts</div>'}</div>
        </div>
      `
    }).join('')

    return `
      <div class="presentation-block">
        <div class="presentation-header">
          <span>User ID: ${escapeHtml(presentation.user_id)}</span>
          <span class="presentation-score">${escapeHtml(presentation.score)}%</span>
        </div>
        ${questions || '<div class="question-block empty">No questions</div>'}
      </div>
    `
  }).join('')

  return `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8" />
        <title>${escapeHtml(assessmentTitle)}</title>
        <style>
          body { font-family: "Inter", Arial, sans-serif; margin: 40px; color: #111827; }
          .print-title { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
          .print-meta { font-size: 12px; color: #6b7280; margin-bottom: 20px; }
          .presentation-block { border: 1px solid #e0e6ed; border-radius: 10px; padding: 5px 7px; margin-bottom: 6px; }
          .presentation-header { display: flex; justify-content: space-between; font-weight: 600; }
          .presentation-score { color: #111827; }
          .question-block { padding: 4px 0; border-top: 1px solid #eef2f6; }
          .question-block:first-of-type { border-top: none; }
          .question-stem { font-weight: 600; font-size: 13px; }
          .question-score { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
          .attempt-row { display: flex; gap: 10px; font-size: 12px; padding: 2px 0; }
          .attempt-row.empty { color: #9ca3af; }
          .attempt-time { min-width: 110px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        </style>
      </head>
      <body>
        <div class="print-title">${escapeHtml(assessmentTitle)}</div>
        <div class="print-meta">${escapeHtml(printedAt)} · ${escapeHtml(schemeLabel)}</div>
        ${rows || '<div class="question-block empty">No questions</div>'}
      </body>
    </html>
  `
}
