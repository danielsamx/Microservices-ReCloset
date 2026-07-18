<script setup>
import { onMounted, watch } from 'vue'
import Navbar from './components/Navbar.vue'
import { useAuth } from './store/auth'
import { useNotifications } from './store/notifications'

const auth = useAuth()
const notif = useNotifications()

function boot() {
  if (auth.isAuthed) { notif.load().catch(() => {}); notif.subscribe() }
}
onMounted(boot)
watch(() => auth.token, boot)
</script>

<template>
  <Navbar />
  <main class="max-w-6xl mx-auto px-4 py-6">
    <router-view />
  </main>
</template>
