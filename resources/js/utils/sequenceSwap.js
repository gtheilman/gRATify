export const buildSequenceSwap = (items, currentId, direction) => {
  if (!Array.isArray(items) || !items.length)
    return null

  const sorted = [...items].sort((a, b) => a.sequence - b.sequence)
  const index = sorted.findIndex(item => item.id === currentId)
  if (index === -1)
    return null

  const targetIndex = direction === 'up' ? index - 1 : index + 1
  if (targetIndex < 0 || targetIndex >= sorted.length)
    return null

  const current = sorted[index]
  const neighbor = sorted[targetIndex]

  return {
    current,
    neighbor,
    currentSequence: current.sequence,
    neighborSequence: neighbor.sequence,
  }
}
