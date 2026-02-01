export const extractPasswordStatus = async (data, error) => {
  let statusFlag = data?.status || error?.data?.status || error?.status

  if (!statusFlag && error?.response?.clone) {
    try {
      const json = await error.response.clone().json()

      statusFlag = json?.status
    } catch {
      // ignore parse errors
    }
  }

  if (!statusFlag && error?.response?.clone) {
    try {
      const json = await error.response.clone().json()

      statusFlag = json?.status || json?.message
    } catch {
      // ignore parse errors
    }
  }

  return statusFlag
}
