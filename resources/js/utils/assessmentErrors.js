import { extractApiErrorMessage } from '@/utils/apiError'

const contextMap = {
  progress: {
    label: 'progress',
    forbidden: 'Forbidden: you do not have access to this progress view.',
    server: 'Server error: unable to load progress right now.',
  },
  feedback: {
    label: 'feedback',
    forbidden: 'Forbidden: you do not have access to this feedback view.',
    server: 'Server error: unable to load feedback right now.',
  },
  scores: {
    label: 'scores',
    forbidden: 'Forbidden: you do not have access to these scores.',
    server: 'Server error: unable to load scores right now.',
  },
}

export const formatAssessmentError = (response, data, context) => {
  const detail = extractApiErrorMessage(data)
  const config = contextMap[context] || { label: 'data', forbidden: 'Forbidden.', server: 'Server error.' }

  if (response.status === 401)
  {return detail ? `Unauthorized: ${detail}` : 'Unauthorized: please sign in again.'}
  if (response.status === 403)
  {return detail ? `Forbidden: ${detail}` : config.forbidden}
  if (response.status === 404)
  {return detail ? `Not found: ${detail}` : 'Not found: assessment does not exist.'}
  if (response.status >= 500)
  {return detail ? `Server error: ${detail}` : config.server}

  return detail || `Unable to load ${config.label}`
}
