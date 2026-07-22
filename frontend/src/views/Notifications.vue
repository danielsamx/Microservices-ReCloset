<script setup>
import NotificationsPanel from '../components/NotificationsPanel.vue'
import { useNotifications } from '../store/notifications'
const notif = useNotifications()
</script>
<template>
  <div class="max-w-2xl mx-auto">
    <div class="mb-4">
      <h1 class="font-display font-bold text-2xl sm:text-3xl">Notificaciones</h1>
      <p class="text-sm text-faint mt-0.5">{{ notif.unread ? `${notif.unread} sin leer` : 'Estás al día' }}</p>
    </div>
    <NotificationsPanel />
  </div>
</template>
