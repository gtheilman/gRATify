import { createFetch } from '@vueuse/core'
import { destr } from 'destr'
import { getXsrfToken } from '@/utils/csrf'

const apiBase = '/api'

const apiFetch = createFetch({
  baseUrl: apiBase,
  fetchOptions: {
    headers: {
      Accept: 'application/json',
    },
  },
  options: {
    refetch: true,
    async beforeFetch({ options }) {
      const xsrfToken = getXsrfToken()
      options.credentials = 'same-origin'
      if (xsrfToken) {
        options.headers = {
          ...options.headers,
          'X-XSRF-TOKEN': xsrfToken,
        }
      }
      // JSON-encode plain object bodies to avoid [object Object] payloads
      if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
        options.headers = {
          'Content-Type': 'application/json',
          ...options.headers,
        }
        options.body = JSON.stringify(options.body)
      }
      
      return { options }
    },
    afterFetch(ctx) {
      const { data, response } = ctx

      // Parse data if it's JSON
      let parsedData = null
      try {
        parsedData = destr(data)
      }
      catch (error) {
        console.error(error)
      }
      
      return { data: parsedData, response }
    },
    onFetchError(ctx) {
      // Preserve existing behavior but do not auto-logout/redirect on 401/419.
      const { error, response } = ctx
      if (response && error) {
        const status = response.status
        error.status = status
        if (!error.message || error.message === 'Failed to fetch') {
          if (status === 401)
            error.message = 'Unauthorized: please sign in again.'
          else if (status === 403)
            error.message = 'Forbidden: you do not have access to this resource.'
          else if (status === 404)
            error.message = 'Not found.'
          else if (status === 409)
            error.message = 'Conflict: the request could not be completed.'
          else if (status === 419)
            error.message = 'Session expired: please refresh and try again.'
          else if (status === 422)
            error.message = 'Validation failed.'
          else if (status >= 500)
            error.message = 'Server error: please try again later.'
        }
      }
      return ctx
    },
  },
})

export const useApi = (...args) => {
  if (typeof args[0] === 'undefined' || args[0] === null) {
    console.error('useApi called without a URL', args)
    args[0] = ''
  }
  return apiFetch(...args)
}
