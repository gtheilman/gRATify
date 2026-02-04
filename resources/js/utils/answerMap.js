export const buildAnswerMap = questions => {
  const map = new Map()
  if (!questions)
  {return map}
  questions.forEach(question => {
    question?.answers?.forEach(ans => {
      map.set(ans.id, { question_id: question.id, correct: Boolean(Number(ans.correct)) })
    })
  })
  
  return map
}
