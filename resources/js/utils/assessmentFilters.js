import { filterEditableAssessments } from '@/utils/assessmentLocking'

export const filterAssessments = (assessments, options = {}) => {
  const term = String(options.term || '').trim().toLowerCase()
  const activeFilter = options.activeFilter || 'all'
  const showEditableOnly = !!options.showEditableOnly

  const filtered = (assessments || []).filter(item => {
    const haystack = [
      item.owner_username,
      item.title,
      item.course,
      item.scheduled_at,
      item.active ? 'active' : 'inactive',
    ].join(' ').toLowerCase()

    const matchesSearch = !term || haystack.includes(term)

    const matchesActive = activeFilter === 'all'
      || (activeFilter === 'active' && item.active)
      || (activeFilter === 'inactive' && !item.active)

    return matchesSearch && matchesActive
  })

  return filterEditableAssessments(filtered, showEditableOnly)
}
