import { shouldTriggerFullscreen } from '@/utils/fullscreenShortcut'

export const handleFullscreenShortcut = (event, enterFullscreen) => {
  if (shouldTriggerFullscreen(event))
  {enterFullscreen?.()}
}
