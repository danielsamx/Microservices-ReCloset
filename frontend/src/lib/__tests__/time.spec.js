import { describe, it, expect } from 'vitest'
import { clockTime, dayKey, timeAgo } from '../time'

describe('utilidades de tiempo', () => {
  it('clockTime formatea HH:MM con ceros', () => {
    const iso = new Date(2026, 0, 1, 9, 5).toISOString()
    expect(clockTime(iso)).toBe('09:05')
  })

  it('dayKey agrupa por el mismo día', () => {
    const a = new Date(2026, 0, 1, 1, 0).toISOString()
    const b = new Date(2026, 0, 1, 23, 59).toISOString()
    expect(dayKey(a)).toBe(dayKey(b))
  })

  it('timeAgo devuelve "Ahora" para el instante actual', () => {
    expect(timeAgo(new Date().toISOString())).toBe('Ahora')
  })

  it('timeAgo devuelve cadena vacía sin fecha', () => {
    expect(timeAgo(null)).toBe('')
  })
})
