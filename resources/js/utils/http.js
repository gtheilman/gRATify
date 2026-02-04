import { getXsrfToken } from '@/utils/csrf'
import { extractApiErrorMessage } from '@/utils/apiError'

// Shared fetch helpers to standardize JSON parsing and error envelopes.
const statusFallbackMessage = status => {
  if (status === 401)
  {return 'Unauthorized: please sign in again.'}
  if (status === 403)
  {return 'Forbidden: you do not have access to this resource.'}
  if (status === 404)
  {return 'Not found.'}
  if (status === 409)
  {return 'Conflict: the request could not be completed.'}
  if (status === 419)
  {return 'Session expired: please refresh and try again.'}
  if (status === 422)
  {return 'Validation failed.'}
  if (status >= 500)
  {return 'Server error: please try again later.'}
  
  return ''
}

const buildHeaders = (headers = {}) => {
  const xsrfToken = getXsrfToken()
  if (!xsrfToken)
  {return headers}
  
  return {
    ...headers,
    'X-XSRF-TOKEN': xsrfToken,
  }
}

export const buildHttpError = (response, data, fallback = 'Request failed') => {
  const status = response?.status ?? 0
  const message = extractApiErrorMessage(data) || statusFallbackMessage(status) || fallback
  const error = new Error(message)

  error.status = status
  error.data = data
  
  return error
}

export const ensureCsrfCookie = () => {
  return fetch('/csrf-cookie', { credentials: 'same-origin' })
}

export const fetchJson = async (url, options = {}) => {
  const {
    method = 'GET',
    body,
    headers = {},
    credentials = 'same-origin',
    parseText = false,
  } = options

  let finalBody = body

  const finalHeaders = buildHeaders({
    Accept: 'application/json',
    ...headers,
  })

  // Mirror useApi behavior: auto-encode JSON bodies.
  if (body && typeof body === 'object' && !(body instanceof FormData)) {
    finalHeaders['Content-Type'] = 'application/json'
    finalBody = JSON.stringify(body)
  }

  const response = await fetch(url, {
    method,
    body: finalBody,
    headers: finalHeaders,
    credentials,
  })

  const contentType = response.headers?.get?.('content-type') || ''
  let data = null
  let text = null
  if (contentType.includes('json')) {
    try {
      data = await response.json()
    }
    catch {
      data = null
    }
  } else if (parseText) {
    try {
      text = await response.text()
    }
    catch {
      text = null
    }
  }

  return { data, response, text }
}

export const fetchJsonOrThrow = async (url, options = {}) => {
  const { data, response } = await fetchJson(url, options)
  if (!response.ok)
  {throw buildHttpError(response, data)}
  
  return { data, response }
}

export const fetchBlobOrThrow = async (url, options = {}) => {
  const {
    method = 'GET',
    body,
    headers = {},
    credentials = 'same-origin',
  } = options

  const finalHeaders = buildHeaders({
    Accept: 'application/json',
    ...headers,
  })

  const response = await fetch(url, {
    method,
    body,
    headers: finalHeaders,
    credentials,
  })

  if (!response.ok) {
    const contentType = response.headers?.get?.('content-type') || ''
    let data = null
    if (contentType.includes('json')) {
      try {
        data = await response.json()
      }
      catch {
        data = null
      }
    }
    throw buildHttpError(response, data)
  }

  const blob = await response.blob()
  
  return { blob, response }
}
