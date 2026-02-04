// Shared helpers to normalize API error envelopes into human-friendly messages.
export const extractApiErrorMessage = payload => {
  if (!payload)
  {return ''}

  if (payload.error && typeof payload.error === 'object') {
    if (payload.error.message)
    {return payload.error.message}
  }

  if (payload.message)
  {return payload.message}

  if (payload.status)
  {return payload.status}

  const { errors } = payload
  if (Array.isArray(errors) && errors.length)
  {return errors[0]}
  if (errors && typeof errors === 'object') {
    const [firstValue] = Object.values(errors)
    if (Array.isArray(firstValue))
    {return firstValue[0] || ''}
    
    return firstValue || ''
  }

  return ''
}

export const getErrorMessage = (err, fallback = '') => {
  if (!err)
  {return fallback}

  if (typeof err === 'string')
  {return err}

  if (typeof err.message === 'string' && err.message !== '[object Object]')
  {return err.message}

  const dataMessage = extractApiErrorMessage(err.data || err.response?.data || err)
  if (dataMessage)
  {return dataMessage}

  if (err.message && typeof err.message === 'object') {
    const nested = extractApiErrorMessage(err.message)
    if (nested)
    {return nested}
  }

  if (typeof err.message === 'string')
  {return err.message}

  return fallback
}

export const readApiErrorDetail = async response => {
  const contentType = response.headers.get('content-type') || ''
  try {
    if (contentType.includes('json')) {
      const json = await response.json()
      
      return extractApiErrorMessage(json) || JSON.stringify(json)
    }
    
    return (await response.text())?.trim()
  }
  catch {
    return ''
  }
}
