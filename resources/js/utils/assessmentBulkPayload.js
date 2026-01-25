export const buildAssessmentBulkPayload = (assessment, questions) => {
  return {
    assessment: { ...assessment },
    questions: (questions || []).map(q => ({
      id: q.id,
      title: (q.title || '').trim() || q.stem,
      stem: q.stem,
      sequence: q.sequence,
      answers: (q.answers || []).map(ans => ({
        id: ans.id,
        answer_text: ans.answer_text,
        correct: ans.correct,
        sequence: ans.sequence,
      })),
    })),
  }
}
