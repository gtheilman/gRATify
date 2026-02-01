const encoder = new TextEncoder()
const decoder = new TextDecoder()

const toBytes = value => encoder.encode(value)
const fromBytes = bytes => decoder.decode(bytes)

const base64Encode = bytes => {
  let binary = ''
  bytes.forEach(b => {
    binary += String.fromCharCode(b)
  })
  
  return btoa(binary)
}

const base64Decode = value => {
  const binary = atob(value)
  const bytes = new Uint8Array(binary.length)
  for (let i = 0; i < binary.length; i += 1) {
    bytes[i] = binary.charCodeAt(i)
  }
  
  return bytes
}

const buildMask = key => {
  if (!key)
    return new Uint8Array([0])
  
  return toBytes(key)
}

const xorBytes = (bytes, mask) => {
  const out = new Uint8Array(bytes.length)
  for (let i = 0; i < bytes.length; i += 1) {
    out[i] = bytes[i] ^ mask[i % mask.length]
  }
  
  return out
}

export const scramble = (value, key) => {
  if (typeof value !== 'string')
    value = JSON.stringify(value ?? '')
  const mask = buildMask(key)
  const bytes = toBytes(value)
  
  return base64Encode(xorBytes(bytes, mask))
}

export const descramble = (value, key) => {
  if (!value || typeof value !== 'string')
    return value
  const mask = buildMask(key)
  try {
    const bytes = base64Decode(value)
    const plain = fromBytes(xorBytes(bytes, mask))
    
    return plain
  } catch {
    return value
  }
}
