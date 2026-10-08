export function queryFilterValue(query, key, allowlist) {
  const raw = query?.[key]
  const value = Array.isArray(raw) ? raw[0] : raw
  return typeof value === 'string' && allowlist.includes(value) ? value : ''
}
