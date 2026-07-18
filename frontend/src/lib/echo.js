import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
window.Pusher = Pusher

let echo = null
export function getEcho() {
  if (echo) return echo
  const token = localStorage.getItem('recloset_token')
  echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'recloset-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: (import.meta.env.VITE_API_URL || 'http://localhost:8080/api') + '/broadcasting/auth',
    auth: { headers: { Authorization: `Bearer ${token}` } },
  })
  return echo
}
export function resetEcho() {
  if (echo) { try { echo.disconnect() } catch (e) {} echo = null }
}
