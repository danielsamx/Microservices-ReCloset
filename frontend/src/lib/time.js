const DAYS = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
const MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']

const hhmm = (d) => `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
const sameDay = (a, b) => a.toDateString() === b.toDateString()

/** "Ahora", "Hace 5 min", "Hace 2 h", "Ayer", "Lun, 14:30", "12 jul 2024" */
export function timeAgo(iso) {
  if (!iso) return ''
  const d = new Date(iso); const now = new Date()
  const s = Math.floor((now - d) / 1000)
  if (s < 60) return 'Ahora'
  if (s < 3600) return `Hace ${Math.floor(s / 60)} min`
  if (sameDay(d, now)) return `Hace ${Math.floor(s / 3600)} h`
  const yest = new Date(now); yest.setDate(now.getDate() - 1)
  if (sameDay(d, yest)) return 'Ayer'
  if (s < 7 * 86400) return `${DAYS[d.getDay()]}, ${hhmm(d)}`
  const year = d.getFullYear() !== now.getFullYear() ? ` ${d.getFullYear()}` : ''
  return `${d.getDate()} ${MONTHS[d.getMonth()]}${year}`
}

/** Hora corta para la burbuja de chat: "14:30" */
export function clockTime(iso) {
  if (!iso) return ''
  return hhmm(new Date(iso))
}

/** Etiqueta separadora de día: "Hoy", "Ayer", "Lun 12 jul" */
export function dayLabel(iso) {
  if (!iso) return ''
  const d = new Date(iso); const now = new Date()
  if (sameDay(d, now)) return 'Hoy'
  const yest = new Date(now); yest.setDate(now.getDate() - 1)
  if (sameDay(d, yest)) return 'Ayer'
  const year = d.getFullYear() !== now.getFullYear() ? ` ${d.getFullYear()}` : ''
  return `${DAYS[d.getDay()]} ${d.getDate()} ${MONTHS[d.getMonth()]}${year}`
}

/** Clave de día para agrupar mensajes */
export function dayKey(iso) {
  const d = new Date(iso)
  return `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`
}
