export const resolveGroupLabel = presentation => {
  const rawGroup = presentation?.group_label ?? presentation?.group_id ?? presentation?.user_id ?? presentation?.id
  
  return rawGroup ?? presentation?.id
}
