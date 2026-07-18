import { defineStore } from 'pinia'
import api from '../lib/api'
import { getEcho } from '../lib/echo'
import { useAuth } from './auth'

export const useNotifications = defineStore('notifications', {
  state: () => ({ items: [], unread: 0, subscribed: false }),
  actions: {
    async load() {
      const [{ data: list }, { data: c }] = await Promise.all([
        api.get('/notifications'),
        api.get('/notifications/unread-count'),
      ])
      this.items = list.notifications; this.unread = c.count
    },
    async markRead(id) {
      await api.patch(`/notifications/${id}/read`)
      const n = this.items.find((x) => x.id === id)
      if (n && !n.read_at) { n.read_at = new Date().toISOString(); this.unread = Math.max(0, this.unread - 1) }
    },
    async markAll() {
      await api.patch('/notifications/read-all')
      this.items.forEach((n) => (n.read_at = n.read_at || new Date().toISOString()))
      this.unread = 0
    },
    subscribe() {
      const auth = useAuth()
      if (this.subscribed || !auth.user) return
      getEcho().private(`notifications.${auth.user.id}`)
        .listen('.notification.created', (e) => {
          this.items.unshift(e.notification); this.unread += 1
        })
      this.subscribed = true
    },
  },
})
