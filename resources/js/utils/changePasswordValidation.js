export const validateChangePasswordForm = (form, isAuthenticated) => {
  if (!isAuthenticated)
    return 'You must be logged in.'
  if (!form?.old_password || !form?.new_password || !form?.new_password_confirmation)
    return 'Please fill in all fields.'
  if (form.new_password !== form.new_password_confirmation)
    return 'New passwords do not match.'
  return ''
}
