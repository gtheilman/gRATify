export const countCorrectAttempts = (attempts, correctSet) => {
  if (!attempts || !correctSet)
    return 0
  let correct = 0
  attempts.forEach(att => {
    if (correctSet.has(att.answer_id))
      correct += 1
  })
  return correct
}
