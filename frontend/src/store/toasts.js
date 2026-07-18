import { defineStore } from 'pinia'
let seq = 0
export const useToasts = defineStore('toasts', {
  state: () => ({ items: [] }),
  actions: {
    push(type, message, timeout = 3500) {
      const id = ++seq
      this.items.push({ id, type, message })
      if (timeout) setTimeout(() => this.dismiss(id), timeout)
      return id
    },
    success(m, t) { return this.push('success', m, t) },
    error(m, t) { return this.push('error', m, t ?? 5000) },
    info(m, t) { return this.push('info', m, t) },
    dismiss(id) { this.items = this.items.filter((x) => x.id !== id) },
  },
})
