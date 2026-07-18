import { defineStore } from 'pinia'
import api from '../lib/api'
import { resetEcho } from '../lib/echo'

export const useAuth = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('recloset_user') || 'null'),
    token: localStorage.getItem('recloset_token') || null,
  }),
  getters: { isAuthed: (s) => !!s.token },
  actions: {
    persist() {
      localStorage.setItem('recloset_token', this.token || '')
      localStorage.setItem('recloset_user', JSON.stringify(this.user))
    },
    async register(payload) {
      const { data } = await api.post('/auth/register', payload)
      this.token = data.token; this.user = data.user; this.persist()
    },
    async login(payload) {
      const { data } = await api.post('/auth/login', payload)
      this.token = data.token; this.user = data.user; this.persist()
    },
    async logout() {
      try { await api.post('/auth/logout') } catch (e) {}
      this.token = null; this.user = null
      localStorage.removeItem('recloset_token'); localStorage.removeItem('recloset_user')
      resetEcho()
    },
  },
})
