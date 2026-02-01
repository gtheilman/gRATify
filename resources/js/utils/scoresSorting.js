import { normalizeUserId } from '@/utils/textFormat'

export const sortPresentations = (presentations, sortKey) => {
  const list = [...(presentations || [])]
  if (sortKey === 'score_desc')
    return list.sort((a, b) => (Number(b.score) || 0) - (Number(a.score) || 0))
  if (sortKey === 'score_asc')
    return list.sort((a, b) => (Number(a.score) || 0) - (Number(b.score) || 0))

  return list.sort((a, b) => {
    const aId = normalizeUserId(a.user_id)
    const bId = normalizeUserId(b.user_id)
    if (typeof aId === 'number' && typeof bId === 'number')
      return aId - bId
    
    return String(aId || '').localeCompare(String(bId || ''))
  })
}
