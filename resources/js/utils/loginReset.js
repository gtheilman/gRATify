export const validateResetEmail = email => {
  if (!email || !String(email).trim())
    return 'Enter your email to receive a reset link.'
  return ''
}
