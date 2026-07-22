<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotifications } from '../store/notifications'
import { useToasts } from '../store/toasts'
import { timeAgo } from '../lib/time'
import EmptyState from './ui/EmptyState.vue'
import Skeleton from './ui/Skeleton.vue'
import ConfirmDialog from './ui/ConfirmDialog.vue'
import Icon from './ui/Icon.vue'

const emit = defineEmits(['navigate'])
const notif = useNotifications(); const toasts = useToasts(); const router = useRouter()
const loading = ref(true)
const confirm = ref({ open: false, mode: null, id: null, loading: false })

onMounted(async () => { try { await notif.load() } catch (e) {} finally { loading.value = false } })

function open(n) {
  notif.markRead(n.id).catch(() => {})
  if (n.data?.conversation_id) { emit('navigate'); router.push(`/chat/${n.data.conversation_id}`) }
}
function askDeleteOne(id) { confirm.value = { open: true, mode: 'one', id, loading: false } }
function askDeleteAll() { confirm.value = { open: true, mode: 'all', id: null, loading: false } }

async function doConfirm() {
  confirm.value.loading = true
  try {
    if (confirm.value.mode === 'one') { await notif.remove(confirm.value.id); toasts.success('Notificación eliminada') }
    else { await notif.removeAll(); toasts.success('Todas las notificaciones fueron eliminadas') }
    confirm.value.open = false
  } catch (e) {
    toasts.error('No se pudo eliminar. Inténtalo de nuevo.')
    confirm.value.loading = false
  }
}
</script>

<template>
  <div class="flex flex-col min-h-0">
    <!-- actions -->
    <div v-if="notif.items.length" class="flex items-center gap-2 px-1 pb-2 shrink-0">
      <button v-if="notif.unread" @click="notif.markAll()" class="btn btn-soft btn-sm">
        <Icon name="check" :size="14" /> Marcar leídas
      </button>
      <button @click="askDeleteAll" class="btn btn-danger btn-sm ml-auto">
        <Icon name="trash" :size="14" /> Eliminar todas
      </button>
    </div>

    <!-- loading -->
    <div v-if="loading" class="space-y-2">
      <div v-for="i in 4" :key="i" class="card p-3">
        <Skeleton h="0.9rem" w="55%" /><div class="mt-2"><Skeleton h=".75rem" w="80%" /></div>
      </div>
    </div>

    <!-- empty -->
    <EmptyState v-else-if="!notif.items.length" icon="bell" title="Todo al día"
      subtitle="Aquí aparecerán tus mensajes y avisos nuevos." />

    <!-- list -->
    <div v-else class="space-y-2 overflow-y-auto -mx-1 px-1">
      <div v-for="n in notif.items" :key="n.id"
        class="group card p-3 flex gap-3 items-start transition hover:shadow-card-hover animate-fade-up"
        :class="{ 'ring-1 ring-brand-400/40 bg-brand-500/5': !n.read_at }">
        <button @click="open(n)" class="flex gap-3 items-start flex-1 min-w-0 text-left">
          <span class="w-9 h-9 rounded-xl grid place-items-center shrink-0"
            :class="n.read_at ? 'bg-brand-500/10 text-faint' : 'bg-gradient-to-br from-brand-300 to-brand-600 text-white'">
            <Icon name="message" :size="18" />
          </span>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="font-semibold text-sm truncate" :class="n.read_at ? 'text-muted' : 'text-body'">{{ n.title }}</p>
              <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-brand shrink-0 animate-glow-pulse" title="No leída"></span>
            </div>
            <p class="text-sm text-muted mt-0.5 line-clamp-2">{{ n.body }}</p>
            <p class="text-[11px] text-faint mt-1">{{ timeAgo(n.created_at) }}</p>
          </div>
        </button>
        <button @click.stop="askDeleteOne(n.id)"
          class="w-8 h-8 grid place-items-center rounded-lg text-faint hover:text-danger-600 hover:bg-danger-500/10 transition shrink-0
                 opacity-100 md:opacity-0 md:group-hover:opacity-100 md:focus:opacity-100"
          aria-label="Eliminar notificación">
          <Icon name="trash" :size="16" />
        </button>
      </div>
    </div>

    <ConfirmDialog :open="confirm.open" danger :loading="confirm.loading"
      :title="confirm.mode === 'all' ? 'Eliminar todas las notificaciones' : 'Eliminar notificación'"
      :message="confirm.mode === 'all'
        ? `Se eliminarán las ${notif.items.length} notificaciones de forma permanente. Esta acción no se puede deshacer.`
        : 'Esta notificación se eliminará de forma permanente. Esta acción no se puede deshacer.'"
      confirmText="Eliminar" @confirm="doConfirm" @cancel="confirm.open = false" />
  </div>
</template>
