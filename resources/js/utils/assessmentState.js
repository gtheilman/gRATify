export const parseActiveFlag = value => {
  if (typeof value === 'boolean')
    return value
  if (value === null || typeof value === 'undefined')
    return null
  
  return !!Number(value)
}
