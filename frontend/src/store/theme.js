import { defineStore } from 'pinia'

const KEY = 'recloset-theme'

function resolveInitial() {
  const saved = localStorage.getItem(KEY)
  if (saved === 'light' || saved === 'dark') return saved
  return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function apply(mode) {
  const root = document.documentElement
  root.classList.toggle('dark', mode === 'dark')
  root.style.colorScheme = mode
}

export const useTheme = defineStore('theme', {
  state: () => ({ mode: 'light' }),
  getters: {
    isDark: (s) => s.mode === 'dark',
  },
  actions: {
    init() {
      this.mode = resolveInitial()
      apply(this.mode)
    },
    toggle() {
      this.mode = this.mode === 'dark' ? 'light' : 'dark'
      localStorage.setItem(KEY, this.mode)
      apply(this.mode)
    },
    set(mode) {
      this.mode = mode
      localStorage.setItem(KEY, mode)
      apply(mode)
    },
  },
})
