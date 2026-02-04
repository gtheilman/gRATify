import { getErrorMessage } from '@/utils/apiError'

export const formatLoginError = err => {
  const message = getErrorMessage(err, '')
  if (!message || message === '[object Object]')
  {return 'Login failed'}
  
  return message
}
