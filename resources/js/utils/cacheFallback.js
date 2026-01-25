import { buildStaleNotice } from '@/utils/cacheNotice'

export const applyCachedFallback = ({ cached, applyData, applyNotice, formatter }) => {
  if (!cached?.data)
    return false

  applyData?.(cached.data)
  applyNotice?.(buildStaleNotice(cached.cachedAt, formatter))
  return true
}
