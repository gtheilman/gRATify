export const offlineBannerMessage = 'You are offline. Changes may not save. We will retry when you reconnect.'

export const buildOfflineRetry = router => {
  return () => {
    router?.go?.(0)
  }
}
