export const collectCorrectAnswerIds = questions => {
  const ids = []
  if (!questions)
    return ids
  questions.forEach(question => {
    question?.answers?.forEach(ans => {
      if (Number(ans.correct))
        ids.push(ans.id)
    })
  })
  return ids
}
