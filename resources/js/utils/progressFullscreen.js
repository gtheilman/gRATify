export const getFullscreenElement = () => {
  if (typeof document === 'undefined')
  {return null}
  
  return document.documentElement
}

export const requestFullscreen = element => {
  if (!element)
  {return false}
  if (element.requestFullscreen) {
    element.requestFullscreen()
    
    return true
  }
  if (element.webkitRequestFullscreen) {
    element.webkitRequestFullscreen()
    
    return true
  }
  if (element.msRequestFullscreen) {
    element.msRequestFullscreen()
    
    return true
  }
  
  return false
}
