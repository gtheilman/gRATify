const correctMap = new Map()

export const setCorrectMap = entries => {
  correctMap.clear()
  if (!entries)
    return
  if (entries instanceof Map) {
    entries.forEach((value, key) => {
      correctMap.set(Number(key), value)
    })
    
    return
  }
  if (Array.isArray(entries)) {
    entries.forEach(([key, value]) => {
      correctMap.set(Number(key), value)
    })
  }
}

export const getCorrectForAnswer = answerId => {
  if (answerId === null || typeof answerId === 'undefined')
    return null
  const key = Number(answerId)
  if (!correctMap.has(key))
    return null
  
  return correctMap.get(key)
}
