import { extractApiErrorMessage } from '@/utils/apiError'

export const normalizeAikenMessage = (message, requireErrorPrefix = true) => {
  if (!message)
    return null
  let cleaned = String(message)
    .replace(/string\(\d+\)/gi, '')
    .replace(/["']/g, '')
    .replace(/&amp;/g, '&')
    .replace(/\s+/g, ' ')
    .trim()
  if (!cleaned)
    return null
  if (requireErrorPrefix) {
    const errorIndex = cleaned.toLowerCase().indexOf('error')
    if (errorIndex === -1)
      return null
    if (errorIndex > 0)
      cleaned = cleaned.slice(errorIndex)
    const errorPrefix = /^error\s*:?\s*/i
    if (!errorPrefix.test(cleaned))
      return null
    cleaned = cleaned.replace(errorPrefix, '')
  } else {
    cleaned = cleaned.replace(/^error\s*:?\s*/i, '')
  }
  cleaned = cleaned.replace(/questionnotcompleteon line\s*(\d+)/i, 'Question not complete on line $1')
  cleaned = cleaned.replace(/\s+on\s+line\s*(\d+)/i, ' on line $1')
  if (cleaned[0] && cleaned[0] === cleaned[0].toLowerCase())
    cleaned = cleaned[0].toUpperCase() + cleaned.slice(1)
  return cleaned
}

export const extractAikenErrorLines = text => {
  if (!text)
    return []
  const normalized = String(text)
    .replace(/\\n/g, '\n')
    .replace(/\\r/g, '\r')
    .replace(/\\"/g, '"')
  const quotedMatches = []
  const quotedRegex = /"([^"]*)"/g
  let match = quotedRegex.exec(normalized)
  while (match) {
    if (match[1] && match[1].includes('Error:'))
      quotedMatches.push(match[1])
    match = quotedRegex.exec(normalized)
  }
  if (quotedMatches.length)
    return quotedMatches.map(item => item.trim())
  const matches = normalized.match(/Error:[\s\S]*?(?=string\(\d+\)|\{\\"status\\"|$)/gi)
    || normalized.match(/Error:[^\n\r]+/gi)
    || []
  return matches.map(item => item.trim())
}

export const buildAikenMessages = (json, rawText) => {
  const messages = []
  if (Array.isArray(json?.errors)) {
    const cleanedErrors = json.errors
      .map(msg => normalizeAikenMessage(msg, false))
      .filter(Boolean)
    return cleanedErrors.length ? cleanedErrors : ['Invalid Aiken format text file.']
  } else if (json?.errors && typeof json.errors === 'object') {
    Object.values(json.errors).forEach(value => {
      if (Array.isArray(value))
        messages.push(...value)
      else if (value)
        messages.push(value)
    })
  }
  const apiMessage = extractApiErrorMessage(json)
  if (apiMessage)
    messages.push(apiMessage)
  if (rawText) {
    const errorMatches = extractAikenErrorLines(rawText)
    messages.push(...errorMatches)
    if (!errorMatches.length)
      messages.push(rawText)
  }

  if (apiMessage) {
    const errorMatches = extractAikenErrorLines(apiMessage)
    messages.push(...errorMatches)
  }

  const cleaned = messages
    .map(message => normalizeAikenMessage(message))
    .filter(Boolean)

  return cleaned.length ? cleaned : ['Invalid Aiken format text file.']
}
