export const resolveAuthNavigation = (to, authState) => {
  const user = authState?.user

  // If a logged-in user hits /login, we should logout first (route stays).
  if (to?.name === 'login' && user)
    return { shouldLogout: true, redirect: null }

  if (authState?.forcePasswordReset && to?.name !== 'change-password')
    return { shouldLogout: false, redirect: { name: 'change-password' } }

  if (!to?.meta?.public && !user && to?.name !== 'login')
    return { shouldLogout: false, redirect: { name: 'login' } }

  if (to?.meta?.requiresAdmin) {
    const role = user?.role
    const normalizedRole = role === 'poobah' ? 'admin' : role
    if (normalizedRole !== 'admin')
      return { shouldLogout: false, redirect: { name: 'root' } }
  }

  return { shouldLogout: false, redirect: null }
}
