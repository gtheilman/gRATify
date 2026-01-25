export const schemeLabelFor = scheme => {
  if (scheme === 'linear-decay-with-zeros')
    return 'Linear decay with zeros'
  if (scheme === 'linear-decay')
    return 'Linear decay'
  return 'Geometric decay'
}
