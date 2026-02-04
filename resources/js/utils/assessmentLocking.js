export const isAssessmentLocked = assessment => {
  const presentationCount = Number(assessment?.presentations_count ?? assessment?.attempts_count ?? 0)
  const hasPresentationsArray = Array.isArray(assessment?.presentations) && assessment.presentations.length > 0
  const hasAttemptsArray = Array.isArray(assessment?.attempts) && assessment.attempts.length > 0
  
  return presentationCount > 0 || hasPresentationsArray || hasAttemptsArray
}

export const filterEditableAssessments = (assessments, showEditableOnly) => {
  if (!showEditableOnly)
  {return assessments || []}
  
  return (assessments || []).filter(assessment => !isAssessmentLocked(assessment))
}
