import { isAssessmentLocked } from '@/utils/assessmentLocking'

const compareStrings = (a, b, dir) => {
  const res = (a || '').localeCompare(b || '', undefined, { sensitivity: 'base' })
  return dir === 'asc' ? res : -res
}

export const sortAssessments = (assessments, key, direction) => {
  const list = [...(assessments || [])]
  const dir = direction === 'desc' ? 'desc' : 'asc'

  const sorters = {
    title: (a, b) => compareStrings(a.title, b.title, dir),
    course: (a, b) => compareStrings(a.course, b.course, dir),
    owner: (a, b) => compareStrings(a.owner_username, b.owner_username, dir),
    scheduled_at: (a, b) => {
      const da = a.scheduled_at ? new Date(a.scheduled_at).getTime() : 0
      const db = b.scheduled_at ? new Date(b.scheduled_at).getTime() : 0
      const res = da - db
      return dir === 'asc' ? res : -res
    },
    active: (a, b) => {
      const av = a.active ? 1 : 0
      const bv = b.active ? 1 : 0
      const res = av - bv
      return dir === 'asc' ? res : -res
    },
    actions: (a, b) => {
      const av = isAssessmentLocked(a) ? 1 : 0
      const bv = isAssessmentLocked(b) ? 1 : 0
      const res = av - bv
      if (res !== 0)
        return dir === 'asc' ? res : -res
      return compareStrings(a.title, b.title, dir)
    },
  }

  const sorter = sorters[key]
  if (!sorter)
    return list
  return list.sort(sorter)
}
