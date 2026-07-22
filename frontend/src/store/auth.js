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
    setSession(data) {
      this.token = data.token
      this.user = data.user
      this.persist()
    },
    async register(payload) {
      const { data } = await api.post('/auth/register', payload)
      this.setSession(data)
      return data
    },
    // Devuelve { requires_2fa, challenge } si el usuario tiene 2FA activo;
    // en caso contrario inicia sesión y devuelve { user, token }.
    async login(payload) {
      const { data } = await api.post('/auth/login', payload)
      if (data.requires_2fa) return data
      this.setSession(data)
      return data
    },
    // Completa el login tras el reto 2FA.
    async verifyTwoFactor({ challenge, code }) {
      const { data } = await api.post('/auth/2fa/verify', { challenge, code })
      this.setSession(data)
      return data
    },
    async logout() {
      try { await api.post('/auth/logout') } catch (e) {}
      this.token = null; this.user = null
      localStorage.removeItem('recloset_token'); localStorage.removeItem('recloset_user')
      resetEcho()
    },

    // ---- Recuperación de contraseña ----
    async forgotPassword(email) {
      const { data } = await api.post('/auth/password/forgot', { email })
      return data
    },
    async resetPassword(payload) {
      const { data } = await api.post('/auth/password/reset', payload)
      return data
    },

    // ---- Verificación de correo ----
    async verifyEmail(payload) {
      const { data } = await api.post('/auth/email/verify', payload)
      if (data.user && this.user && data.user.id === this.user.id) {
        this.user = data.user; this.persist()
      }
      return data
    },
    async resendVerification() {
      const { data } = await api.post('/auth/email/resend')
      return data
    },

    // ---- Doble factor (2FA) ----
    async enableTwoFactor() {
      const { data } = await api.post('/auth/2fa/enable')
      return data // { challenge }
    },
    async confirmTwoFactor({ challenge, code }) {
      const { data } = await api.post('/auth/2fa/confirm', { challenge, code })
      if (data.user) { this.user = data.user; this.persist() }
      return data
    },
    async disableTwoFactor(password) {
      const { data } = await api.post('/auth/2fa/disable', { password })
      if (data.user) { this.user = data.user; this.persist() }
      return data
    },
    // Refresca el usuario actual desde el backend.
    async refreshMe() {
      const { data } = await api.get('/auth/me')
      this.user = data.user; this.persist()
      return data.user
    },
  },
})
