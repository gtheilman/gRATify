export const handleLogoutAction = async ({ logout, push }) => {
  await logout()
  push({ name: 'login' })
}
