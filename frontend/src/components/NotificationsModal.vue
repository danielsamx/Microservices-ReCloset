<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useNotifications } from '../store/notifications'
import Modal from './ui/Modal.vue'
import EmptyState from './ui/EmptyState.vue'
import Skeleton from './ui/Skeleton.vue'
import Icon from './ui/Icon.vue'

const notif = useNotifications()
const router = useRouter()
const loading = ref(false)

// Load notifications when modal is opened
watch(() => notif.showModal, async (open) => {
  if (open) {
    loading.value = true
    try { 
      await notif.load() 
    } catch (e) {
      console.error(e)
    } finally { 
      loading.value = false 
    }
  }
})

function openNotif(n) {
  notif.markRead(n.id)
  notif.showModal = false // Close modal
  if (n.data?.conversation_id) {
    router.push(`/chat/${n.data.conversation_id}`)
  }
}

function ago(iso) {
  if (!iso) return ''
  const s = Math.floor((Date.now() - new Date(iso)) / 1000)
  if (s < 60) return 'ahora'
  if (s < 3600) return `hace ${Math.floor(s/60)} min`
  if (s < 86400) return `hace ${Math.floor(s/3600)} h`
  return `hace ${Math.floor(s/86400)} d`
}
</script>

<template>
  <Modal :open="notif.showModal" title="Notificaciones" @close="notif.showModal = false">
    <div class="mt-4 max-h-[60vh] overflow-y-auto pr-1">
      <div v-if="notif.items.length" class="flex justify-end mb-3">
        <button @click="notif.markAll()" class="btn btn-soft btn-sm">
          <Icon name="check" :size="15" /> Marcar todas leídas
        </button>
      </div>

      <div v-if="loading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="card p-3">
          <Skeleton h="1.2rem" w="60%" />
          <div class="mt-2"><Skeleton h=".9rem" w="80%" /></div>
        </div>
      </div>

      <EmptyState v-else-if="!notif.items.length" icon="bell" title="Todo al día"
        subtitle="Aquí aparecerán tus mensajes y avisos nuevos." />

      <div v-else class="space-y-2">
        <button v-for="n in notif.items" :key="n.id" @click="openNotif(n)"
          class="w-full text-left card p-3.5 hover:shadow-card-hover transition flex gap-3 items-start"
          :class="{ 'ring-1 ring-brand-200 bg-brand-50/40': !n.read_at }">
          <span class="w-8 h-8 rounded-lg grid place-items-center shrink-0" 
            :class="n.read_at ? 'bg-slate-100 text-slate-400' : 'bg-brand-100 text-brand-700'">
            <Icon name="message" :size="16" />
          </span>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="font-semibold text-xs text-slate-800 truncate">{{ n.title }}</p>
              <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-brand shrink-0"></span>
              <span class="ml-auto text-[10px] text-slate-400 shrink-0">{{ ago(n.created_at) }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5 truncate">{{ n.body }}</p>
          </div>
        </button>
      </div>
    </div>
  </Modal>
</template>
