export const shouldResetPageOnPageSizeChange = (prev, next) => {
  return prev !== next
}

export const shouldResetPageOnToggle = (prev, next) => {
  return prev !== next
}
