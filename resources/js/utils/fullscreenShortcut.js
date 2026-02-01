export const shouldTriggerFullscreen = event => {
  const key = event?.key
  
  return typeof key === 'string' && key.toLowerCase() === 'f'
}
