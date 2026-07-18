export function mediaUrl(path) {
  if (!path) return ''
  if (/^https?:\/\//.test(path)) return path
  const base = (import.meta.env.VITE_API_URL || 'http://localhost:8080/api').replace(/\/api$/, '')
  return base + path
}
export function money(v) {
  return new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(v || 0)
}
