<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotifications } from '../store/notifications'
const notif = useNotifications(); const router = useRouter()
onMounted(() => notif.load())
function open(n) {
  notif.markRead(n.id)
  if (n.data?.conversation_id) router.push(`/chat/${n.data.conversation_id}`)
}
</script>
<template>
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Notificaciones</h1>
    <button @click="notif.markAll()" class="text-sm text-brand">Marcar todas como leídas</button>
  </div>
  <p v-if="!notif.items.length" class="text-center text-slate-400 py-16">No tienes notificaciones.</p>
  <div v-else class="space-y-2">
    <button v-for="n in notif.items" :key="n.id" @click="open(n)"
      class="w-full text-left bg-white border rounded-xl p-3 hover:bg-slate-50"
      :class="{ 'border-brand/40': !n.read_at }">
      <div class="flex items-center gap-2">
        <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-brand"></span>
        <p class="font-medium text-sm">{{ n.title }}</p>
      </div>
      <p class="text-sm text-slate-500 mt-0.5">{{ n.body }}</p>
    </button>
  </div>
</template>
