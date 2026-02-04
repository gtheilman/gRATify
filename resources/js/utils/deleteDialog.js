export const clearDeleteDialog = (pendingRef, showRef) => {
  if (pendingRef)
  {pendingRef.value = null}
  if (showRef)
  {showRef.value = false}
}
