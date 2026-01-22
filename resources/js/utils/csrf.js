// Pulls the XSRF token from the cookie set by Sanctum.
import Cookies from 'universal-cookie'

export const getXsrfToken = () => {
  const cookies = new Cookies()
  const token = cookies.get('XSRF-TOKEN')
  return token ? decodeURIComponent(token) : null
}
