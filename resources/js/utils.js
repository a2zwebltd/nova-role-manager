export function formatGuardName(name) {
  if (!name) return ''
  let formatted = name.replace(/-/g, ' ').toUpperCase()
  // Replace "SUPPLIER" with "SUPPLIER DASHBOARD"
  if (formatted === 'SUPPLIER') {
    formatted = 'SUPPLIER DASHBOARD'
  }
  return formatted
}


